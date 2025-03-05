<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Statistics Model
 *
 * Enregistre les actions liées aux rendez-vous.
 *
 * @package Models
 */
class Statistics_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'appointment_id' => 'integer',
        'user_id' => 'integer',
        'provider_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'appointment_id' => 'appointment_id',
        'action' => 'action',
        'timestamp' => 'timestamp',
        'user_id' => 'user_id',
        'provider_id' => 'provider_id',
    ];

    /**
     * Enregistre une action liée à un rendez-vous.
     *
     * @param array $data
     *
     * @return int Retourne l'ID de l'entrée insérée.
     *
     * @throws RuntimeException
     */
    public function log_appointment_action(array $data): int
    {
        $data['timestamp'] = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
        if ($this->db->insert('appointment_statistics', $data)) {
            log_message('debug', 'Statistique ajoutée : ' . json_encode($data));
            return $this->db->insert_id();
        } else {
            log_message('error', 'Erreur lors de l’insertion de la statistique.');
            return 0;
        }
    }
    

    /**
     * Récupère les statistiques des rendez-vous
     *
     * @param array|string|null $where Conditions WHERE optionnelles
     * @param int|null $limit Nombre d’enregistrements à récupérer
     * @param int|null $offset Offset de récupération
     * @param string|null $order_by Ordre des résultats
     *
     * @return array
     */
    public function get_statistics(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }
    
        if ($order_by !== null) {
            $this->db->order_by($order_by);
        }
    
        return $this->db->get('appointment_statistics', $limit, $offset)->result_array();
    }
    


    public function get_daily_statistics($date)
    {
        $this->db->select('action, COUNT(*) as total');
        $this->db->from('appointment_statistics');
        $this->db->where('DATE(timestamp)', $date);
        $this->db->group_by('action');
        $query = $this->db->get();
    
        $result = $query->result_array();
    
        // Organiser les résultats sous un format clair
        $stats = [
            'date' => $date,
            'created' => 0,
            'modified' => 0,
            'deleted' => 0
        ];
    
        foreach ($result as $row) {
            if ($row['action'] == 'created') {
                $stats['created'] = (int) $row['total'];
            } elseif ($row['action'] == 'modified') {
                $stats['modified'] = (int) $row['total'];
            } elseif ($row['action'] == 'deleted') {
                $stats['deleted'] = (int) $row['total'];
            }
        }
    
        return $stats;
    }


}
