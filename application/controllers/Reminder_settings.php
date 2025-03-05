<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reminder_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('settings_model');
    }

    /**
     * Affiche la page des paramètres.
     */
    public function index(): void
    {
        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            redirect('login');
            return;
        }

        $reminder_settings = $this->settings_model->get('name = "reminder_enable"');

        script_vars([
            'reminder_settings' => $reminder_settings,
            'csrf_token'        => $this->security->get_csrf_hash(),
        ]);

        html_vars([
            'page_title'  => lang('reminder_settings'),
            'active_menu' => 'reminder_settings',
        ]);

        $this->load->view('pages/reminder_settings');
    }

    /**
     * Sauvegarde les paramètres.
     */
    public function save(): void
    {
        try {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have permission to edit settings.');
            }

            $settings = request('reminder_settings', []);

            foreach ($settings as $setting) {
                $existing = $this->settings_model
                    ->query()
                    ->where('name', $setting['name'])
                    ->get()
                    ->row_array();

                if (!empty($existing)) {
                    $setting['id'] = $existing['id'];
                }

                $this->settings_model->save($setting);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
