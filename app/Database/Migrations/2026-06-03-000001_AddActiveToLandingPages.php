<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActiveToLandingPages extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('active', 'landing_pages')) {
            $this->forge->addColumn('landing_pages', [
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            ]);
        }

        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('active', 'landing_pages')) {
            $this->forge->dropColumn('landing_pages', 'active');
        }

        $this->db->resetDataCache();
    }
}
