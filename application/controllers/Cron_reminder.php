<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron_reminder extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointments_model');
        $this->load->model('settings_model');
    }

    public function index()
    {
        echo "[Cron_reminder] Début d'exécution...\n";
        log_message('info', "[Cron_reminder] Début d'exécution...");

        // Vérifier si le module de rappel est activé
        $enable = setting('reminder_enable');
        if (!$enable) {
            echo "[Cron_reminder] Rappel désactivé => Arrêt.\n";
            log_message('info', "[Cron_reminder] Rappel désactivé => Arrêt.");
            return;
        }

        // Déterminer la plage horaire pour demain à cette heure
        $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        $start_time = clone $now;
        $start_time->modify('+1 day');
        $start_time->setTime($now->format('H'), 0, 0);

        $end_time = clone $start_time;
        $end_time->modify('+1 hour');

        echo "[Cron_reminder] Recherche des rendez-vous entre " . $start_time->format('Y-m-d H:i:s') . " et " . $end_time->format('Y-m-d H:i:s') . "\n";
        log_message('info', "[Cron_reminder] Recherche des rendez-vous entre " . $start_time->format('Y-m-d H:i:s') . " et " . $end_time->format('Y-m-d H:i:s'));

        $appointments = $this->appointments_model->get_appointments_between($start_time->format('Y-m-d H:i:s'), $end_time->format('Y-m-d H:i:s'));

        if (empty($appointments)) {
            echo "[Cron_reminder] Aucun rendez-vous à rappeler.\n";
            log_message('info', "[Cron_reminder] Aucun rendez-vous à rappeler.");
            return;
        }

        foreach ($appointments as $appointment) {
            if ($appointment['reminder_sent'] == 1) {
                echo "[Cron_reminder] RDV ID " . $appointment['id'] . " déjà rappelé.\n";
                log_message('info', "[Cron_reminder] RDV ID " . $appointment['id'] . " déjà rappelé.");
                continue;
            }

            $customer = $this->db
                ->select('users.phone_number, users.first_name, users.timezone')
                ->where('users.id', $appointment['id_users_customer'])
                ->get('ea_users')
                ->row_array();

            if (empty($customer) || empty($customer['phone_number'])) {
                echo "[Cron_reminder] Informations client incomplètes pour RDV ID " . $appointment['id'] . "\n";
                log_message('error', "[Cron_reminder] Informations client incomplètes pour RDV ID " . $appointment['id']);
                continue;
            }

            $tz = new DateTimeZone($customer['timezone'] ?? 'Europe/Paris');
            $start_dt = new DateTime($appointment['start_datetime'], $tz);
            $dateFormatee = $start_dt->format('d/m/Y');
            $heureFormatee = $start_dt->format('H:i');

            $smsText = sprintf(
                "Bonjour, je vous rappelle votre rendez-vous demain à %s.",
                $heureFormatee
            );

            echo "[Cron_reminder] Envoi du SMS à " . $customer['phone_number'] . "\n";
            log_message('info', "[Cron_reminder] Envoi du SMS à " . $customer['phone_number']);

            $this->send_sms($customer['phone_number'], $smsText);
            $this->appointments_model->mark_as_reminded($appointment['id']);

            echo "[Cron_reminder] RDV ID " . $appointment['id'] . " marqué comme rappel envoyé.\n";
            log_message('info', "[Cron_reminder] RDV ID " . $appointment['id'] . " marqué comme rappel envoyé.");
        }

        echo "[Cron_reminder] Fin d'exécution.\n";
        log_message('info', "[Cron_reminder] Fin d'exécution.");
    }

    private function send_sms($phone, $message)
    {
        $payload = $phone . '&' . $message;
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen('tcp-server', 5002, $errno, $errstr, 5);

        if (!$socket) {
            echo "[Cron_reminder] Erreur connexion SMS : $errno - $errstr\n";
            log_message('error', "[Cron_reminder] Erreur connexion SMS : $errno - $errstr");
        } else {
            fwrite($socket, $payload . "\n");
            fclose($socket);
            echo "[Cron_reminder] SMS envoyé : $payload\n";
            log_message('info', "[Cron_reminder] SMS envoyé : $payload");
        }
    }
}