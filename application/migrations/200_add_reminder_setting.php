<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_reminder_setting extends CI_Migration {

    public function up()
    {
        // Vérifier si l'option reminder_enabled existe déjà
        $query = $this->db->get_where('settings', ['name' => 'reminder_enabled']);

        if ($query->num_rows() == 0) {
            // Ajouter le paramètre dans la table settings
            $this->db->insert('settings', [
                'name' => 'reminder_enabled',
                'value' => '0' // Désactivé par défaut
            ]);
        }
    }

    public function down()
    {
        // Supprimer le paramètre reminder_enabled
        $this->db->delete('settings', ['name' => 'reminder_enabled']);
    }
}
