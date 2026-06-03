<?php

use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Database/Migrations/2026-05-20-000001_RefactorLandingPagesForFileUpload.php';

use App\Database\Migrations\RefactorLandingPagesForFileUpload;

use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class RefactorLandingPagesMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';

    public function testMigrationUpDown(): void
    {
        $db = \Config\Database::connect();
        $migration = new RefactorLandingPagesForFileUpload();

        // 1. Assert UP state (applied by CI4 test framework auto-migration)
        $this->assertFalse($db->tableExists('block_templates'));
        $this->assertTrue($db->fieldExists('file_path', 'landing_pages'));
        $this->assertFalse($db->fieldExists('blocks', 'landing_pages'));
        $this->assertFalse($db->fieldExists('custom_css', 'landing_pages'));

        // 2. Run down() to test rollback
        $migration->down();
        $db->resetDataCache();

        // 3. Assert DOWN state
        $this->assertTrue($db->tableExists('block_templates'));
        $this->assertFalse($db->fieldExists('file_path', 'landing_pages'));
        $this->assertTrue($db->fieldExists('blocks', 'landing_pages'));
        $this->assertTrue($db->fieldExists('custom_css', 'landing_pages'));

        // 4. Run up() again to restore
        $migration->up();
        $db->resetDataCache();

        // 5. Assert restored to UP state
        $this->assertFalse($db->tableExists('block_templates'));
        $this->assertTrue($db->fieldExists('file_path', 'landing_pages'));
        $this->assertFalse($db->fieldExists('blocks', 'landing_pages'));
        $this->assertFalse($db->fieldExists('custom_css', 'landing_pages'));
    }

    public function testUpIsIdempotent(): void
    {
        $db = \Config\Database::connect();
        $migration = new RefactorLandingPagesForFileUpload();

        // Run up() twice
        $migration->up();
        $db->resetDataCache();

        // Should still be in the expected state
        $this->assertFalse($db->tableExists('block_templates'));
        $this->assertTrue($db->fieldExists('file_path', 'landing_pages'));
        $this->assertFalse($db->fieldExists('blocks', 'landing_pages'));
        $this->assertFalse($db->fieldExists('custom_css', 'landing_pages'));
    }
}
