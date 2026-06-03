<?php

namespace Tests\unit;

use App\Controllers\LandingPagesController;
use App\Models\LandingPageModel;
use Tests\TestCase;

/**
 * @internal
 */
final class LandingPagesControllerTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
        $this->testDir = WRITEPATH . 'landing_pages';
        if (! is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeTestDir($this->testDir);
    }

    private function removeTestDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeTestDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createTestHtmlFile(string $slug): void
    {
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html><body><h1>Test</h1></body></html>');
    }

    public function testCreateReturnsUploadForm(): void
    {
        $result = $this->withURI('http://localhost/landing-pages/create')
            ->controller(LandingPagesController::class)
            ->execute('create');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('New Landing Page', $result->getBody());
        $this->assertStringContainsString('enctype="multipart/form-data"', $result->getBody());
        $this->assertStringContainsString('index_html', $result->getBody());
    }

    public function testEditReturnsBlockEditor(): void
    {
        $model = new LandingPageModel();
        $slug  = 'edit-test';
        $this->createTestHtmlFile($slug);
        $id = $model->insert([
            'title'     => 'Edit Test',
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/landing-pages/edit/' . $id)
            ->controller(LandingPagesController::class)
            ->execute('edit', $id);

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Edit Test', $result->getBody());
    }

    public function testEditWithBlocksRendersTextareas(): void
    {
        $slug = 'blocks-test';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html><body>
            <!-- BLOCO_1_INICIO -->Hello<!-- BLOCO_1_FIM -->
            <!-- BLOCO_2_INICIO -->World<!-- BLOCO_2_FIM -->
        </body></html>');

        $model = new LandingPageModel();
        $id = $model->insert([
            'title'     => 'Blocks Page',
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/landing-pages/edit/' . $id)
            ->controller(LandingPagesController::class)
            ->execute('edit', $id);

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Bloco 1', $result->getBody());
        $this->assertStringContainsString('Bloco 2', $result->getBody());
        $this->assertStringContainsString('Hello', $result->getBody());
        $this->assertStringContainsString('World', $result->getBody());
    }

    public function testEditWithNoBlocksFallsBackToRawHtml(): void
    {
        $slug = 'no-blocks';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html><body><p>No delimiters here</p></body></html>');

        $model = new LandingPageModel();
        $id = $model->insert([
            'title'     => 'No Blocks',
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/landing-pages/edit/' . $id)
            ->controller(LandingPagesController::class)
            ->execute('edit', $id);

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('raw_html', $result->getBody());
        $this->assertStringContainsString('No delimiters here', $result->getBody());
    }

    public function testStoreCreatesBasicRecord(): void
    {
        $model = new LandingPageModel();
        $id = $model->insert([
            'title'     => 'Store Test',
            'slug'      => 'store-test',
            'file_path' => null,
        ]);

        $this->assertNotFalse($id);
        $page = $model->findBySlug('store-test');
        $this->assertNotNull($page);
    }

    public function testDeleteRemovesDirAndRecord(): void
    {
        $slug = 'to-delete';
        $this->createTestHtmlFile($slug);

        $model = new LandingPageModel();
        $id = $model->insert([
            'title'     => 'To Delete',
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $this->assertNotNull($model->find($id));
        $this->assertTrue(is_dir($this->testDir . '/' . $slug));

        $controller = new LandingPagesController();
        $result = $controller->delete($id);

        $this->assertNull($model->find($id));
    }
}
