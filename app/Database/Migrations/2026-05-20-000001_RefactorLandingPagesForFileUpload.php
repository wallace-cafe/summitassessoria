<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorLandingPagesForFileUpload extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();

        // 1. Drop block_templates table entirely
        $this->forge->dropTable('block_templates', true);

        // 2. Drop blocks, custom_css from landing_pages; add file_path
        $dbDriver = $this->db->getPlatform();
        $lpTable = $this->db->prefixTable('landing_pages');
        $lpNewTable = $this->db->prefixTable('landing_pages_new');
        if (strtolower($dbDriver) === 'sqlite3' || strtolower($dbDriver) === 'sqlite') {
            $version = $this->db->query('SELECT sqlite_version() AS version')->getRow()->version;
            if (version_compare($version, '3.35.0', '<')) {
                $this->db->query('PRAGMA foreign_keys = OFF;');

                $this->forge->addField([
                    'id'         => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'title'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                    'slug'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                    'file_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ]);
                $this->forge->addKey('id', true);
                $this->forge->createTable('landing_pages_new');

                $this->db->query("INSERT INTO {$lpNewTable} (id, title, slug, created_at, updated_at) SELECT id, title, slug, created_at, updated_at FROM {$lpTable};");

                $this->forge->dropTable('landing_pages', true);
                $this->db->query("ALTER TABLE {$lpNewTable} RENAME TO {$lpTable};");
                $this->db->query("CREATE UNIQUE INDEX landing_pages_slug ON {$lpTable} (slug);");

                $this->db->query('PRAGMA foreign_keys = ON;');
            } else {
                if ($this->db->fieldExists('blocks', 'landing_pages')) {
                    $this->db->query("ALTER TABLE {$lpTable} DROP COLUMN blocks");
                }
                if ($this->db->fieldExists('custom_css', 'landing_pages')) {
                    $this->db->query("ALTER TABLE {$lpTable} DROP COLUMN custom_css");
                }

                if (! $this->db->fieldExists('file_path', 'landing_pages')) {
                    $this->forge->addColumn('landing_pages', [
                        'file_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                    ]);
                }
            }
        } else {
            if ($this->db->fieldExists('blocks', 'landing_pages')) {
                $this->forge->dropColumn('landing_pages', 'blocks');
            }
            if ($this->db->fieldExists('custom_css', 'landing_pages')) {
                $this->forge->dropColumn('landing_pages', 'custom_css');
            }
            if (! $this->db->fieldExists('file_path', 'landing_pages')) {
                $this->forge->addColumn('landing_pages', [
                    'file_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                ]);
            }
        }

        $this->db->resetDataCache();
    }

    public function down()
    {
        $this->db->resetDataCache();

        // 1. Re-create block_templates table
        if (! $this->db->tableExists('block_templates')) {
            $this->forge->addField([
                'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'html_template' => ['type' => 'TEXT', 'null' => false],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('block_templates');
        }

        // 2. Drop file_path, restore blocks and custom_css columns
        $dbDriver = $this->db->getPlatform();
        $lpTable = $this->db->prefixTable('landing_pages');
        $lpOldTable = $this->db->prefixTable('landing_pages_old');
        if (strtolower($dbDriver) === 'sqlite3' || strtolower($dbDriver) === 'sqlite') {
            $version = $this->db->query('SELECT sqlite_version() AS version')->getRow()->version;
            if (version_compare($version, '3.35.0', '<')) {
                $this->db->query('PRAGMA foreign_keys = OFF;');

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
                $this->forge->createTable('landing_pages_old');

                $this->db->query("INSERT INTO {$lpOldTable} (id, title, slug, created_at, updated_at) SELECT id, title, slug, created_at, updated_at FROM {$lpTable};");

                $this->forge->dropTable('landing_pages', true);
                $this->db->query("ALTER TABLE {$lpOldTable} RENAME TO {$lpTable};");
                $this->db->query("CREATE UNIQUE INDEX landing_pages_slug ON {$lpTable} (slug);");

                $this->db->query('PRAGMA foreign_keys = ON;');
            } else {
                if ($this->db->fieldExists('file_path', 'landing_pages')) {
                    $this->db->query("ALTER TABLE {$lpTable} DROP COLUMN file_path");
                }

                if (! $this->db->fieldExists('blocks', 'landing_pages')) {
                    $this->forge->addColumn('landing_pages', [
                        'blocks'     => ['type' => 'TEXT', 'null' => true],
                        'custom_css' => ['type' => 'TEXT', 'null' => true],
                    ]);
                }
            }
        } else {
            if ($this->db->fieldExists('file_path', 'landing_pages')) {
                $this->forge->dropColumn('landing_pages', 'file_path');
            }
            if (! $this->db->fieldExists('blocks', 'landing_pages')) {
                $this->forge->addColumn('landing_pages', [
                    'blocks'     => ['type' => 'TEXT', 'null' => true],
                    'custom_css' => ['type' => 'TEXT', 'null' => true],
                ]);
            }
        }

        $this->db->resetDataCache();
    }
}
