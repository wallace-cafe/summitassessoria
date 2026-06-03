<?php

namespace Tests\unit;

use App\Controllers\PublicController;
use App\Models\LandingPageModel;
use Tests\TestCase;

/**
 * @internal
 */
final class PublicControllerTest extends TestCase
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
        $items = scandir($dir);
        foreach ($items as $item) {
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

    private function createPageWithHtml(string $slug, string $html): int
    {
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', $html);

        $model = new LandingPageModel();
        return $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);
    }

    // --- show() tests ---

    public function testShowWithValidPageReturnsHtmlWithInjectedHiddenField(): void
    {
        $this->createPageWithHtml('with-form', '<html><body><form id="lead-form" action="/p/with-form/lead" method="post"></form></body></html>');

        $result = $this->withURI('http://localhost/p/with-form')
            ->controller(PublicController::class)
            ->execute('show', 'with-form');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('<input type="hidden" name="landing_page_id" value="', $result->getBody());
    }

    public function testShowInjectsBaseTag(): void
    {
        $this->createPageWithHtml('my-page', '<html><head></head><body><p>Content</p></body></html>');

        $result = $this->withURI('http://localhost/p/my-page')
            ->controller(PublicController::class)
            ->execute('show', 'my-page');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('<base href="/p/my-page/">', $result->getBody());
    }

    public function testShowWithMissingIndexHtmlReturns404(): void
    {
        $model = new LandingPageModel();
        $model->insert([
            'title'     => 'no-file',
            'slug'      => 'no-file',
            'file_path' => 'landing_pages/no-file',
        ]);

        $result = $this->withURI('http://localhost/p/no-file')
            ->controller(PublicController::class)
            ->execute('show', 'no-file');

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    public function testShowWithNoLeadFormServesPageWithoutInjection(): void
    {
        $this->createPageWithHtml('no-form', '<html><body><p>No form here</p></body></html>');

        $result = $this->withURI('http://localhost/p/no-form')
            ->controller(PublicController::class)
            ->execute('show', 'no-form');

        $this->assertTrue($result->isOK());
        $this->assertStringNotContainsString('landing_page_id', $result->getBody());
    }

    public function testNonexistentSlugReturns404(): void
    {
        $result = $this->withURI('http://localhost/p/nonexistent')
            ->controller(PublicController::class)
            ->execute('show', 'nonexistent');

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    // --- asset() tests ---

    public function testAssetWithValidFileReturns200(): void
    {
        $slug = 'asset-test';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/style.css', 'body { color: red; }');
        file_put_contents($pageDir . '/index.html', '<html></html>');

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/asset-test/style.css')
            ->controller(PublicController::class)
            ->execute('asset', $slug, 'style.css');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('text/css', $result->response()->getHeaderLine('Content-Type'));
    }

    public function testAssetWithSubdirectoryReturns200(): void
    {
        $slug = 'sub-asset';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        $imgDir = $pageDir . '/images';
        if (! is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }
        file_put_contents($imgDir . '/hero.jpg', 'fake-image-data');
        file_put_contents($pageDir . '/index.html', '<html></html>');

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/sub-asset/images/hero.jpg')
            ->controller(PublicController::class)
            ->execute('asset', $slug, 'images', 'hero.jpg');

        $this->assertTrue($result->isOK());
    }

    public function testAssetPreventsPathTraversal(): void
    {
        $slug = 'traversal-test';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html></html>');

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/traversal-test/../../etc/passwd')
            ->controller(PublicController::class)
            ->execute('asset', $slug, '../../etc/passwd');

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    public function testAssetWithNonExistentFileReturns404(): void
    {
        $slug = 'missing-asset';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html></html>');

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/missing-asset/nonexistent.js')
            ->controller(PublicController::class)
            ->execute('asset', $slug, 'nonexistent.js');

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    public function testAssetWithFileOutsidePageDirReturns404(): void
    {
        $slug = 'outside-test';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html></html>');

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/outside-test/../outside-file.txt')
            ->controller(PublicController::class)
            ->execute('asset', $slug, '../outside-file.txt');

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    public function testAssetSetsCacheControlHeader(): void
    {
        $slug = 'cache-test';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/app.js', 'console.log("test");');
        file_put_contents($pageDir . '/index.html', '<html></html>');

        $model = new LandingPageModel();
        $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/cache-test/app.js')
            ->controller(PublicController::class)
            ->execute('asset', $slug, 'app.js');

        $this->assertTrue($result->isOK());
        $cacheControl = $result->response()->getHeaderLine('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
    }
}
