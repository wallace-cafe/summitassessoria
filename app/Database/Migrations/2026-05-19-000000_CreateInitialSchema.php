<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInitialSchema extends Migration
{
    public function up()
    {
        // Enable WAL mode for better concurrency
        $this->db->query('PRAGMA journal_mode=WAL;');

        // Users table
        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'password'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users');

        // Landing pages table
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'content'    => ['type' => 'TEXT', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('landing_pages');

        // Leads table
        $this->forge->addField([
            'id'              => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'landing_page_id' => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'message'         => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'default' => 'New'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('landing_page_id', 'landing_pages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('leads');
    }

    public function down()
    {
        $this->forge->dropTable('leads', true);
        $this->forge->dropTable('landing_pages', true);
        $this->forge->dropTable('users', true);
    }
}
