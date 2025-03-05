<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migrate extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('migration');
        $this->load->database();
    }

    /**
     * Point d'entrée principal de la migration.
     * Accès : http://votre-domaine/migrate
     */
    public function index()
    {
        // Désactive la sortie directe
        ob_start();

        // Exécute la migration vers la dernière version disponible
        if ($this->migration->latest() === FALSE) {
            show_error($this->migration->error_string());
        } else {
            log_message('debug', "Migrations exécutées avec succès.");
        }

        // Ajoute le paramètre "reminder_enabled"
        $this->add_reminder_setting();

        // Flush output buffer
        ob_end_flush();
    }

    /**
     * Ajoute le paramètre "reminder_enabled" dans la table "settings" si non existant.
     */
    private function add_reminder_setting()
    {
        $reminder_exists = $this->db
            ->get_where('settings', ['name' => 'reminder_enabled'])
            ->num_rows();

        if ($reminder_exists === 0) {
            $data = [
                'name' => 'reminder_enabled',
                'value' => '0',
            ];
            $this->db->insert('settings', $data);
            log_message('debug', 'Paramètre "reminder_enabled" ajouté.');
        } else {
            log_message('debug', 'Paramètre "reminder_enabled" existe déjà.');
        }
    }
}
