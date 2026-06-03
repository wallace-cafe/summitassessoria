<?php

namespace Tests;

class PublicAndLeadsTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        \Config\Services::reset();
        parent::setUp();
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

    private function createPageWithHtml(string $slug, string $html): int
    {
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', $html);

        $model = new \App\Models\LandingPageModel();
        return $model->insert([
            'title'     => $slug,
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);
    }

    public function testPublicPageRenders(): void
    {
        $pageId = $this->createPageWithHtml('public-test', '<html><body><h1>Public Test</h1></body></html>');

        $result = $this->withURI('http://localhost/p/public-test')
            ->controller(\App\Controllers\PublicController::class)
            ->execute('show', 'public-test');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Public Test', $result->getBody());
        $this->assertStringNotContainsString('/css/style.css', $result->getBody());
    }

    public function testPublicPage404(): void
    {
        $result = $this->withURI('http://localhost/p/nonexistent')
            ->controller(\App\Controllers\PublicController::class)
            ->execute('show', 'nonexistent');

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    public function testLeadCaptureStoresData(): void
    {
        $landingPageModel = new \App\Models\LandingPageModel();
        $pageId = $landingPageModel->insert([
            'title'     => 'Lead Capture Test',
            'slug'      => 'lead-capture-test',
            'file_path' => null,
        ]);

        $leadModel = new \App\Models\LeadModel();
        $leadId = $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'John Doe',
            'email'           => 'john@example.com',
            'phone'           => '555-1234',
            'message'         => 'Interested in services',
            'status'          => 'New',
        ]);

        $this->assertNotFalse($leadId);
        $lead = $leadModel->find($leadId);
        $this->assertEquals('John Doe', $lead['name']);
        $this->assertEquals('john@example.com', $lead['email']);
        $this->assertEquals($pageId, $lead['landing_page_id']);
        $this->assertEquals('New', $lead['status']);
    }

    public function testLeadSearchFilters(): void
    {
        $landingPageModel = new \App\Models\LandingPageModel();
        $pageId = $landingPageModel->insert([
            'title'     => 'Search Test',
            'slug'      => 'search-test',
            'file_path' => null,
        ]);

        $leadModel = new \App\Models\LeadModel();
        $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'Alice Smith',
            'email'           => 'alice@example.com',
            'status'          => 'New',
        ]);
        $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'Bob Jones',
            'email'           => 'bob@example.com',
            'status'          => 'New',
        ]);

        $results = $leadModel->search('Alice')->findAll();
        $this->assertCount(1, $results);
        $this->assertEquals('Alice Smith', $results[0]['name']);

        $results = $leadModel->search('example.com')->findAll();
        $this->assertCount(2, $results);
    }

    public function testLeadSourceFilter(): void
    {
        $landingPageModel = new \App\Models\LandingPageModel();
        $page1 = $landingPageModel->insert(['title' => 'Page 1', 'slug' => 'page-1', 'file_path' => null]);
        $page2 = $landingPageModel->insert(['title' => 'Page 2', 'slug' => 'page-2', 'file_path' => null]);

        $leadModel = new \App\Models\LeadModel();
        $leadModel->insert(['landing_page_id' => $page1, 'name' => 'Lead 1', 'email' => 'l1@example.com', 'status' => 'New']);
        $leadModel->insert(['landing_page_id' => $page2, 'name' => 'Lead 2', 'email' => 'l2@example.com', 'status' => 'New']);

        $results = $leadModel->filterByLandingPage($page1)->findAll();
        $this->assertCount(1, $results);
        $this->assertEquals('Lead 1', $results[0]['name']);
    }

    public function testLeadModelDefaultStatus(): void
    {
        $landingPageModel = new \App\Models\LandingPageModel();
        $pageId = $landingPageModel->insert(['title' => 'Status Test', 'slug' => 'status-test', 'file_path' => null]);

        $leadModel = new \App\Models\LeadModel();
        $leadId = $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'Status Lead',
            'email'           => 'status@example.com',
        ]);

        $lead = $leadModel->find($leadId);
        $this->assertEquals('New', $lead['status']);
    }

    public function testDynamicAssetServing(): void
    {
        $slug = 'public-test';
        $pageId = $this->createPageWithHtml($slug, '<html></html>');
        
        $pageDir = WRITEPATH . 'landing_pages/' . $slug;
        $assetsDir = $pageDir . '/assets';
        if (! is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }

        // Create mock assets
        file_put_contents($assetsDir . '/image.png', 'fake png data');
        file_put_contents($assetsDir . '/video.mp4', 'fake mp4 data');
        file_put_contents($assetsDir . '/video.webm', 'fake webm data');
        file_put_contents($assetsDir . '/video.ogg', 'fake ogg data');
        file_put_contents($assetsDir . '/unknown.xyz', 'fake unknown data');

        // Test PNG image serving
        $result = $this->withURI("http://localhost/p/{$slug}/assets/image.png")
            ->controller(\App\Controllers\PublicController::class)
            ->execute('asset', $slug, 'assets', 'image.png');
        $this->assertTrue($result->isOK());
        $this->assertEquals('image/png', $result->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('max-age=31536000', $result->response()->getHeaderLine('Cache-Control'));
        $this->assertEquals('fake png data', $result->response()->getBody());

        // Test MP4 video serving
        $result = $this->withURI("http://localhost/p/{$slug}/assets/video.mp4")
            ->controller(\App\Controllers\PublicController::class)
            ->execute('asset', $slug, 'assets', 'video.mp4');
        $this->assertTrue($result->isOK());
        $this->assertEquals('video/mp4', $result->response()->getHeaderLine('Content-Type'));
        $this->assertEquals('fake mp4 data', $result->response()->getBody());

        // Test WEBM video serving
        $result = $this->withURI("http://localhost/p/{$slug}/assets/video.webm")
            ->controller(\App\Controllers\PublicController::class)
            ->execute('asset', $slug, 'assets', 'video.webm');
        $this->assertTrue($result->isOK());
        $this->assertEquals('video/webm', $result->response()->getHeaderLine('Content-Type'));
        $this->assertEquals('fake webm data', $result->response()->getBody());

        // Test OGG video serving
        $result = $this->withURI("http://localhost/p/{$slug}/assets/video.ogg")
            ->controller(\App\Controllers\PublicController::class)
            ->execute('asset', $slug, 'assets', 'video.ogg');
        $this->assertTrue($result->isOK());
        $this->assertEquals('video/ogg', $result->response()->getHeaderLine('Content-Type'));
        $this->assertEquals('fake ogg data', $result->response()->getBody());

        // Test unknown extension fallback
        $result = $this->withURI("http://localhost/p/{$slug}/assets/unknown.xyz")
            ->controller(\App\Controllers\PublicController::class)
            ->execute('asset', $slug, 'assets', 'unknown.xyz');
        $this->assertTrue($result->isOK());
        $this->assertNotEmpty($result->response()->getHeaderLine('Content-Type'));
        $this->assertEquals('fake unknown data', $result->response()->getBody());
    }
}
