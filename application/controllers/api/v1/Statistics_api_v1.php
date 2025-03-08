<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Statistics API v1 controller.
 *
 * @package Controllers
 */
class Statistics_api_v1 extends EA_Controller
{
    /**
     * Statistics_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');

        // Authentification via API Token
        $this->api->auth();

        // Charger le modèle des statistiques
        $this->api->model('statistics_model');
    }

    /**
     * Get statistics for a given day.
     *
     * Example: GET /api/v1/statistics?date=2025-02-03
     */
    public function index(): void
    {
        try {
            // Récupérer la date depuis les paramètres GET
            $date = $this->input->get('date') ?: date('Y-m-d');


            // Récupérer les statistiques pour cette date
            $stats = $this->statistics_model->get_daily_statistics($date);

            // Retourner les résultats en JSON
            json_response($stats);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
