<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBlocksToLandingPages extends Migration
{
    public function up()
    {
        // 1. Create block_templates table
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'html_template' => ['type' => 'TEXT', 'null' => false],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('block_templates');

        // 2. Add blocks and custom_css columns to landing_pages
        $fields = [
            'blocks'     => ['type' => 'TEXT', 'null' => true],
            'custom_css' => ['type' => 'TEXT', 'null' => true],
        ];
        $this->forge->addColumn('landing_pages', $fields);

        // 3. Drop content column from landing_pages with SQLite version check
        $dbDriver = $this->db->getPlatform();
        $lpTable = $this->db->prefixTable('landing_pages');
        $lpNewTable = $this->db->prefixTable('landing_pages_new');
        if (strtolower($dbDriver) === 'sqlite3' || strtolower($dbDriver) === 'sqlite') {
            $version = $this->db->query('SELECT sqlite_version() AS version')->getRow()->version;
            if (version_compare($version, '3.35.0', '<')) {
                // Table-recreation workaround for older SQLite versions
                $this->db->query('PRAGMA foreign_keys = OFF;');

                // Create a temporary new table without 'content' but including the new columns
                $this->forge->addField([
                    'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'title'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                    'slug'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                    'blocks'     => ['type' => 'TEXT', 'null' => true],
                    'custom_css' => ['type' => 'TEXT', 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ]);
                $this->forge->addKey('id', true);
                $this->forge->createTable('landing_pages_new');

                // Copy data across
                $this->db->query("INSERT INTO {$lpNewTable} (id, title, slug, created_at, updated_at) SELECT id, title, slug, created_at, updated_at FROM {$lpTable};");

                // Drop the old table
                $this->forge->dropTable('landing_pages', true);

                // Rename the new table
                $this->db->query("ALTER TABLE {$lpNewTable} RENAME TO {$lpTable};");

                // Re-create the unique index on slug
                $this->db->query("CREATE UNIQUE INDEX landing_pages_slug ON {$lpTable} (slug);");

                $this->db->query('PRAGMA foreign_keys = ON;');
            } else {
                // Modern SQLite >= 3.35.0 supports DROP COLUMN
                $this->db->query("ALTER TABLE {$lpTable} DROP COLUMN content;");
            }
        } else {
            // Non-sqlite platforms support standard dropColumn
            $this->forge->dropColumn('landing_pages', 'content');
        }
    }

    public function down()
    {
        // 1. Drop block_templates
        $this->forge->dropTable('block_templates', true);

        // 2. Restore content column, drop blocks and custom_css from landing_pages
        $dbDriver = $this->db->getPlatform();
        $lpTable = $this->db->prefixTable('landing_pages');
        $lpOldTable = $this->db->prefixTable('landing_pages_old');
        if (strtolower($dbDriver) === 'sqlite3' || strtolower($dbDriver) === 'sqlite') {
            $version = $this->db->query('SELECT sqlite_version() AS version')->getRow()->version;
            if (version_compare($version, '3.35.0', '<')) {
                // Table-recreation workaround for down migration
                $this->db->query('PRAGMA foreign_keys = OFF;');

                $this->forge->addField([
                    'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'title'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                    'slug'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                    'content'    => ['type' => 'TEXT', 'null' => false],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ]);
                $this->forge->addKey('id', true);
                $this->forge->createTable('landing_pages_old');

                // Copy over data, setting empty string to content
                $this->db->query("INSERT INTO {$lpOldTable} (id, title, slug, content, created_at, updated_at) SELECT id, title, slug, '', created_at, updated_at FROM {$lpTable};");

                $this->forge->dropTable('landing_pages', true);
                $this->db->query("ALTER TABLE {$lpOldTable} RENAME TO {$lpTable};");
                $this->db->query("CREATE UNIQUE INDEX landing_pages_slug ON {$lpTable} (slug);");

                $this->db->query('PRAGMA foreign_keys = ON;');
            } else {
                // Modern SQLite: drop blocks/custom_css and add content back
                $this->db->query("ALTER TABLE {$lpTable} DROP COLUMN blocks;");
                $this->db->query("ALTER TABLE {$lpTable} DROP COLUMN custom_css;");

                $fields = [
                    'content' => ['type' => 'TEXT', 'null' => false, 'default' => ''],
                ];
                $this->forge->addColumn('landing_pages', $fields);
            }
        } else {
            // Other DBs
            $this->forge->dropColumn('landing_pages', 'blocks');
            $this->forge->dropColumn('landing_pages', 'custom_css');
            $fields = [
                'content' => ['type' => 'TEXT', 'null' => false, 'default' => ''],
            ];
            $this->forge->addColumn('landing_pages', $fields);
        }
    }
}
