<?php

namespace Tests;

use App\Models\LandingPageModel;
use App\Models\LeadModel;
use CodeIgniter\HTTP\ResponseInterface;

class ApiLpControllerTest extends TestCase
{
    private \App\Controllers\Api\LpController $lpController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lpController = new \App\Controllers\Api\LpController();
        $this->lpController->initController(
            service('request'),
            service('response'),
            service('logger')
        );
    }

    public function testListReturnsLandingPagesInCorrectFormat(): void
    {
        (new LandingPageModel())->insert([
            'title'     => 'Campanha Agosto',
            'slug'      => 'campanha-agosto',
            'file_path' => 'landing_pages/campanha-agosto',
        ]);

        $result = $this->lpController->list();

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertCount(1, $body['data']);
        $this->assertArrayHasKey('id', $body['data'][0]);
        $this->assertArrayHasKey('title', $body['data'][0]);
        $this->assertArrayHasKey('slug', $body['data'][0]);
        $this->assertArrayHasKey('created_at', $body['data'][0]);
        $this->assertArrayNotHasKey('file_path', $body['data'][0]);
        $this->assertEquals('Campanha Agosto', $body['data'][0]['title']);
    }

    public function testListReturnsEmptyArrayWhenNoLandingPages(): void
    {
        $result = $this->lpController->list();

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertIsArray($body['data']);
        $this->assertCount(0, $body['data']);
    }

    public function testListReturnsResultsOrderedByCreatedAtDesc(): void
    {
        $model = new LandingPageModel();
        $db = \Config\Database::connect();

        $model->insert([
            'title'     => 'Older Page',
            'slug'      => 'older-page',
            'file_path' => 'landing_pages/older-page',
        ]);
        $olderId = $model->getInsertID();
        $db->table('landing_pages')->update(['created_at' => '2026-01-01 00:00:00'], ['id' => $olderId]);

        $model->insert([
            'title'     => 'Newer Page',
            'slug'      => 'newer-page',
            'file_path' => 'landing_pages/newer-page',
        ]);
        $newerId = $model->getInsertID();
        $db->table('landing_pages')->update(['created_at' => '2026-06-01 00:00:00'], ['id' => $newerId]);

        $result = $this->lpController->list();

        $body = json_decode($result->getBody(), true);
        $this->assertCount(2, $body['data']);
        $this->assertEquals('Newer Page', $body['data'][0]['title']);
        $this->assertEquals('Older Page', $body['data'][1]['title']);
    }

    public function testLeadsWithValidSlugReturnsLeads(): void
    {
        $pageId = (new LandingPageModel())->insert([
            'title'     => 'Campanha Agosto',
            'slug'      => 'campanha-agosto',
            'file_path' => 'landing_pages/campanha-agosto',
        ]);
        (new LeadModel())->insert([
            'landing_page_id' => $pageId,
            'name'            => 'João Silva',
            'email'           => 'joao@example.com',
            'phone'           => '11999990000',
            'message'         => 'Quero saber mais.',
            'status'          => 'new',
        ]);

        $result = $this->lpController->leads('campanha-agosto');

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('João Silva', $body['data'][0]['name']);
        $this->assertEquals('joao@example.com', $body['data'][0]['email']);
        $this->assertEquals('11999990000', $body['data'][0]['phone']);
        $this->assertEquals('Quero saber mais.', $body['data'][0]['message']);
        $this->assertEquals('new', $body['data'][0]['status']);
    }

    public function testLeadsWithValidSlugNoLeadsReturnsEmptyArray(): void
    {
        (new LandingPageModel())->insert([
            'title'     => 'Campanha Agosto',
            'slug'      => 'campanha-agosto',
            'file_path' => 'landing_pages/campanha-agosto',
        ]);

        $result = $this->lpController->leads('campanha-agosto');

        $this->assertEquals(200, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertIsArray($body['data']);
        $this->assertCount(0, $body['data']);
    }

    public function testLeadsWithInvalidSlugReturns404(): void
    {
        $result = $this->lpController->leads('slug-inexistente');

        $this->assertEquals(404, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertNull($body['data']);
        $this->assertEquals('Landing page not found', $body['errors']);
    }

    public function testLeadsReturnsOrderedByCreatedAtDesc(): void
    {
        $pageId = (new LandingPageModel())->insert([
            'title'     => 'Campanha',
            'slug'      => 'campanha',
            'file_path' => 'landing_pages/campanha',
        ]);
        $leadModel = new LeadModel();
        $db = \Config\Database::connect();

        $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'Old Lead',
            'email'           => 'old@example.com',
            'phone'           => '111',
            'message'         => 'Old',
            'status'          => 'new',
        ]);
        $olderId = $leadModel->getInsertID();
        $db->table('leads')->update(['created_at' => '2026-01-01 00:00:00'], ['id' => $olderId]);

        $leadModel->insert([
            'landing_page_id' => $pageId,
            'name'            => 'New Lead',
            'email'           => 'new@example.com',
            'phone'           => '222',
            'message'         => 'New',
            'status'          => 'new',
        ]);
        $newerId = $leadModel->getInsertID();
        $db->table('leads')->update(['created_at' => '2026-06-01 00:00:00'], ['id' => $newerId]);

        $result = $this->lpController->leads('campanha');

        $body = json_decode($result->getBody(), true);
        $this->assertCount(2, $body['data']);
        $this->assertEquals('New Lead', $body['data'][0]['name']);
        $this->assertEquals('Old Lead', $body['data'][1]['name']);
    }

    public function testListResponseHasCorrectJsonEnvelope(): void
    {
        $result = $this->lpController->list();
        $body = json_decode($result->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('errors', $body);
    }

    public function testLeadsResponseHasCorrectJsonEnvelopeOnSuccess(): void
    {
        $pageId = (new LandingPageModel())->insert([
            'title'     => 'Test',
            'slug'      => 'test-slug',
            'file_path' => 'landing_pages/test',
        ]);
        (new LeadModel())->insert([
            'landing_page_id' => $pageId,
            'name'            => 'Test',
            'email'           => 'test@test.com',
            'phone'           => '000',
            'message'         => 'Test',
            'status'          => 'new',
        ]);

        $result = $this->lpController->leads('test-slug');
        $body = json_decode($result->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('errors', $body);
    }

    public function testLeadsResponseHasCorrectJsonEnvelopeOn404(): void
    {
        $result = $this->lpController->leads('nao-existe');
        $body = json_decode($result->getBody(), true);

        $this->assertNull($body['data']);
        $this->assertArrayHasKey('meta', $body);
        $this->assertEquals('Landing page not found', $body['errors']);
    }
}
