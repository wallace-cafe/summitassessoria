<?php

use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Database/Migrations/2026-05-20-000000_AddBlocksToLandingPages.php';
require_once APPPATH . 'Database/Migrations/2026-05-20-000001_RefactorLandingPagesForFileUpload.php';

use App\Database\Migrations\AddBlocksToLandingPages;
use App\Database\Migrations\RefactorLandingPagesForFileUpload;

use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class AddBlocksMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';

    public function testMigrationUpDown(): void
    {
        $db = \Config\Database::connect();
        $addBlocks = new AddBlocksToLandingPages();
        $refactor = new RefactorLandingPagesForFileUpload();

        // CI4 auto-migration runs all 3 migrations (up state = no block_templates,
        // no blocks/custom_css, has file_path).
        // Roll back my refactoring migration to get to the AddBlocks UP state.
        $refactor->down();
        $db->resetDataCache();

        // Now roll back the AddBlocks migration to reach the initial state
        // (content column restored, block_templates/columns removed)
        $addBlocks->down();
        $db->resetDataCache();

        // Now apply AddBlocks UP
        $addBlocks->up();
        $db->resetDataCache();

        // Assert the UP state:
        $this->assertTrue($db->tableExists('block_templates'));
        $this->assertTrue($db->fieldExists('name', 'block_templates'));
        $this->assertTrue($db->fieldExists('html_template', 'block_templates'));
        $this->assertFalse($db->fieldExists('content', 'landing_pages'));
        $this->assertTrue($db->fieldExists('blocks', 'landing_pages'));
        $this->assertTrue($db->fieldExists('custom_css', 'landing_pages'));

        // Now run down() to test rollback
        $addBlocks->down();
        $db->resetDataCache();

        // Assert all changes rolled back correctly
        $this->assertFalse($db->tableExists('block_templates'));
        $this->assertTrue($db->fieldExists('content', 'landing_pages'));
        $this->assertFalse($db->fieldExists('blocks', 'landing_pages'));
        $this->assertFalse($db->fieldExists('custom_css', 'landing_pages'));

        // Run up() to restore
        $addBlocks->up();
        $db->resetDataCache();

        // Assert restored to UP state
        $this->assertTrue($db->tableExists('block_templates'));
        $this->assertTrue($db->fieldExists('name', 'block_templates'));
        $this->assertTrue($db->fieldExists('html_template', 'block_templates'));
        $this->assertFalse($db->fieldExists('content', 'landing_pages'));
        $this->assertTrue($db->fieldExists('blocks', 'landing_pages'));
        $this->assertTrue($db->fieldExists('custom_css', 'landing_pages'));
    }
}
