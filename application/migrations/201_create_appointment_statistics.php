<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_appointment_statistics extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true
            ],
            'appointment_id' => [
                'type' => 'INT',
                'constraint' => 11
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 20
            ],
            'timestamp' => [
                'type' => 'DATETIME',
                'default' => 'CURRENT_TIMESTAMP'
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ],
            'provider_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            ]
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('appointment_statistics');
    }

    public function down()
    {
        $this->dbforge->drop_table('appointment_statistics');
    }
}
