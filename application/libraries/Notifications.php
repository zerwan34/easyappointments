<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

/**
 * Notifications library.
 *
 * Handles the notifications related functionality.
 *
 * @package Libraries
 */
class Notifications
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Notifications constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('settings_model');
        $this->CI->load->model('statistics_model');

        $this->CI->load->library('email_messages');
        $this->CI->load->library('ics_file');
        $this->CI->load->library('timezones');
    }

    /**
     * Send the required notifications, related to an appointment creation/modification.
     *
     * @param array $appointment Appointment data.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param array $settings Required settings.
     * @param bool|false $manage_mode Manage mode.
     */
    public function notify_appointment_saved(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        bool $manage_mode = false,
    ): void {
        try {
            $current_language = config('language');
    
            $reschedule_hash = $appointment['hash'];

            $customer_link = Config::BASE_URL . 'index.php/booking/reschedule/' . $reschedule_hash;
            $provider_link = Config::BASE_URL . 'index.php/calendar/reschedule/' . $reschedule_hash;
            
            // Éviter les répétitions
            $customer_link_sms = $customer_link;
            $provider_link_sms = $provider_link;
               
            
            
            $ics_stream    = $this->CI->ics_file->get_stream($appointment, $service, $provider, $customer);
    
            // 1) Notification par e-mail au client
            $send_customer =
                !empty($customer['email']) && filter_var(setting('customer_notifications'), FILTER_VALIDATE_BOOLEAN);
    
            if ($send_customer === true) {
                config(['language' => $customer['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_booked');
                $message = $manage_mode ? '' : lang('thank_you_for_appointment');
    
                $this->CI->email_messages->send_appointment_saved(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $subject,
                    $message,
                    $customer_link,
                    $customer['email'],
                    $ics_stream,
                    $customer['timezone'],
                );
            }
    
            // 2) Notification par SMS (si le client a un numéro de téléphone)
            if (!empty($customer['phone_number'])) {
                // 1) Calcul de la date/heure du rendez-vous selon le fuseau (par ex. provider ou customer)
                $tz = new DateTimeZone($provider['timezone'] ?? 'Europe/Paris'); 
                $start_dt = new DateTime($appointment['start_datetime'], $tz);
            
                // (Si vous voulez afficher l'heure pour le client selon son propre timezone :) 
                // if (!empty($customer['timezone'])) {
                //     $start_dt->setTimezone(new DateTimeZone($customer['timezone']));
                // }
            
                // Formater la date et l’heure (ex. 15/02/2025 et 14:00)
                $dateFormatee  = $start_dt->format('d/m/Y');
                $heureFormatee = $start_dt->format('H:i');
            
                // 2) Construire l’adresse du cabinet
                $provider_location = trim(
                    ($provider['address'] ?? '') . ', ' .
                    ($provider['city'] ?? '')    . ' ' .
                    ($provider['zip_code'] ?? '')
                );
            
                // 3) Créer les deux textes de SMS
            
                // Premier SMS : confirmation
                $sms1 = sprintf(
                    'Votre rendez-vous est confirmé le %s à %s au cabinet situé %s',
                    $dateFormatee,
                    $heureFormatee,
                    $provider_location
                );
            
                // Deuxième SMS : en cas d’imprévu
                $sms2 = sprintf(
                    'En cas d’imprévu %s',
                    $customer_link_sms // par ex. "https://votresite/booking/reschedule/XXXX"
                );
            
                // 4) Pour chacun, on envoie la ligne “PHONE:Message” via fsockopen
                $messages = [$sms1, $sms2];
                foreach ($messages as $messageText) {
                    $payload = $customer['phone_number'] . '&' . $messageText;
            
                    // Tenter la connexion TCP (5 secondes de timeout)
                    $errno  = 0;
                    $errstr = '';
                    $socket = @fsockopen('tcp-server', 5002, $errno, $errstr, 5);
            
                    if (!$socket) {
                        // Gestion d’erreur si on n’arrive pas à se connecter
                        log_message('error', "Impossible de se connecter à tcp-server:5002 pour SMS : $errno - $errstr");
                    } else {
                        // Envoyer la ligne
                        fwrite($socket, $payload . "\n");
                        fclose($socket);
            
                        log_message('info', "SMS envoyé via tcp-server:5002 : $payload");
                    }
                }
            }
    
            // 3) Reste de la fonction : notify provider, admins, etc.
            $send_provider = filter_var(
                $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($send_provider === true) {
                config(['language' => $provider['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                $this->CI->email_messages->send_appointment_saved(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $subject,
                    $message,
                    $provider_link,
                    $provider['email'],
                    $ics_stream,
                    $provider['timezone'],
                );
            }

            // Notify admins.
            $admins = $this->CI->admins_model->get();

            foreach ($admins as $admin) {
                if ($admin['settings']['notifications'] === '0') {
                    continue;
                }

                config(['language' => $admin['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                $this->CI->email_messages->send_appointment_saved(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $subject,
                    $message,
                    $provider_link,
                    $admin['email'],
                    $ics_stream,
                    $admin['timezone'],
                );
            }

            // Notify secretaries.
            $secretaries = $this->CI->secretaries_model->get();

            foreach ($secretaries as $secretary) {
                if ($secretary['settings']['notifications'] === '0') {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers'])) {
                    continue;
                }

                config(['language' => $secretary['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                $this->CI->email_messages->send_appointment_saved(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $subject,
                    $message,
                    $provider_link,
                    $secretary['email'],
                    $ics_stream,
                    $secretary['timezone'],
                );
            }

            $this->CI->statistics_model->log_appointment_action([
                'appointment_id' => $appointment['id'],
                'action' => $manage_mode ? 'modified' : 'created',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $customer['id'] ?? null,
                'provider_id' => $provider['id'],
            ]);


        } catch (Throwable $e) {
            log_message(
                'error',
                'Notifications - Could not email confirmation details of appointment (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        } finally {
            config(['language' => $current_language ?? 'english']);
            $this->CI->lang->load('translations');
        }
    }

    /**
     * Send the required notifications, related to an appointment removal.
     *
     * @param array $appointment Appointment data.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param array $settings Required settings.
     */
    public function notify_appointment_deleted(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        string $cancellation_reason = '',
    ): void {
        try {
            $current_language = config('language');

            // Notify provider.
            $send_provider = filter_var(
                $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($send_provider === true) {
                config(['language' => $provider['language']]);
                $this->CI->lang->load('translations');

                $this->CI->email_messages->send_appointment_deleted(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $provider['email'],
                    $cancellation_reason,
                    $provider['timezone'],
                );
            }

            // Notify customer.
            $send_customer =
                !empty($customer['email']) && filter_var(setting('customer_notifications'), FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === true) {
                config(['language' => $customer['language']]);
                $this->CI->lang->load('translations');

                $this->CI->email_messages->send_appointment_deleted(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $customer['email'],
                    $cancellation_reason,
                    $customer['timezone'],
                );
            }

            // Notify admins.
            $admins = $this->CI->admins_model->get();

            foreach ($admins as $admin) {
                if ($admin['settings']['notifications'] === '0') {
                    continue;
                }

                config(['language' => $admin['language']]);
                $this->CI->lang->load('translations');

                $this->CI->email_messages->send_appointment_deleted(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $admin['email'],
                    $cancellation_reason,
                    $admin['timezone'],
                );
            }

            // Notify secretaries.
            $secretaries = $this->CI->secretaries_model->get();

            foreach ($secretaries as $secretary) {
                if ($secretary['settings']['notifications'] === '0') {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers'])) {
                    continue;
                }

                config(['language' => $secretary['language']]);
                $this->CI->lang->load('translations');

                $this->CI->email_messages->send_appointment_deleted(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $settings,
                    $secretary['email'],
                    $cancellation_reason,
                    $secretary['timezone'],
                );
            }


            $this->CI->statistics_model->log_appointment_action([
                'appointment_id' => $appointment['id'],
                'action' => 'deleted',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $customer['id'] ?? null,
                'provider_id' => $provider['id'],
            ]);


        } catch (Throwable $e) {
            log_message(
                'error',
                'Notifications - Could not email cancellation details of appointment (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        } finally {
            config(['language' => $current_language ?? 'english']);
            $this->CI->lang->load('translations');
        }
    }
}
