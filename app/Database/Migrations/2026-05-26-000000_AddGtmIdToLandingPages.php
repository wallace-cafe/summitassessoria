<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGtmIdToLandingPages extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('gtm_id', 'landing_pages')) {
            $this->forge->addColumn('landing_pages', [
                'gtm_id' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
            ]);
        }

        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('gtm_id', 'landing_pages')) {
            $this->forge->dropColumn('landing_pages', 'gtm_id');
        }

        $this->db->resetDataCache();
    }
}
