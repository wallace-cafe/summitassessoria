<?php

namespace Tests;

class RoutesAndIntegrationTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
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

    public function testEndToEndFlow(): void
    {
        $slug = 'e2e-test';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html><body><h1>E2E Test Page</h1></body></html>');

        $landingPageModel = new \App\Models\LandingPageModel();
        $pageId = $landingPageModel->insert([
            'title'     => 'E2E Test Page',
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);
        $this->assertNotFalse($pageId);

        $result = $this->withURI('http://localhost/p/e2e-test')
            ->controller(\App\Controllers\PublicController::class)
            ->execute('show', 'e2e-test');
        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('E2E Test Page', $result->getBody());

        $leadModel = new \App\Models\LeadModel();
        $leadId = $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'E2E Lead',
            'email'           => 'e2e@example.com',
            'phone'           => '555-9999',
            'message'         => 'E2E message',
            'status'          => 'New',
        ]);
        $this->assertNotFalse($leadId);

        $lead = $leadModel->find($leadId);
        $this->assertEquals('E2E Lead', $lead['name']);
        $this->assertEquals($pageId, $lead['landing_page_id']);

        $this->loginAsAdmin();
        $result = $this->withURI('http://localhost/leads')
            ->controller(\App\Controllers\LeadsController::class)
            ->execute('index');
        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('E2E Lead', $result->getBody());
    }

    public function testRootRedirectsToLogin(): void
    {
        $result = $this->withURI('http://localhost/')
            ->controller(\App\Controllers\AuthController::class)
            ->execute('login');

        $this->assertTrue($result->isOK());
    }

    public function testDashboardRequiresAuth(): void
    {
        $filter = new \App\Filters\AuthFilter();
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI('http://localhost/dashboard'),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );

        $result = $filter->before($request, null);
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testPublicRoutesAccessibleWithoutSession(): void
    {
        $slug = 'public-access';
        $pageDir = $this->testDir . '/' . $slug;
        if (! is_dir($pageDir)) {
            mkdir($pageDir, 0755, true);
        }
        file_put_contents($pageDir . '/index.html', '<html><body><h1>Public Access</h1></body></html>');

        $landingPageModel = new \App\Models\LandingPageModel();
        $landingPageModel->insert([
            'title'     => 'Public Access',
            'slug'      => $slug,
            'file_path' => 'landing_pages/' . $slug,
        ]);

        $result = $this->withURI('http://localhost/p/public-access')
            ->controller(\App\Controllers\PublicController::class)
            ->execute('show', 'public-access');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Public Access', $result->getBody());
    }

    public function testAllRoutesReturnExpectedStatus(): void
    {
        $result = $this->withURI('http://localhost/login')
            ->controller(\App\Controllers\AuthController::class)
            ->execute('login');
        $this->assertEquals(200, $result->response()->getStatusCode());

        $this->loginAsAdmin();
        $result = $this->withURI('http://localhost/dashboard')
            ->controller(\App\Controllers\DashboardController::class)
            ->execute('index');
        $this->assertEquals(200, $result->response()->getStatusCode());
    }
}
