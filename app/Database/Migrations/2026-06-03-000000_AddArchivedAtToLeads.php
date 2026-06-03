<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddArchivedAtToLeads extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        if (! $this->db->fieldExists('archived_at', 'leads')) {
            $this->forge->addColumn('leads', [
                'archived_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            ]);
        }

        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->db->resetDataCache();

        if ($this->db->fieldExists('archived_at', 'leads')) {
            $this->forge->dropColumn('leads', 'archived_at');
        }

        $this->db->resetDataCache();
    }
}
