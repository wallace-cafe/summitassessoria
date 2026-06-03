<?php

use App\Models\LandingPageModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class LandingPageModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $namespace = 'App';

    public function testAllowedFieldsUpdated(): void
    {
        $model = new LandingPageModel();

        $reflector = new \ReflectionClass(LandingPageModel::class);
        $property = $reflector->getProperty('allowedFields');
        $property->setAccessible(true);
        $allowedFields = $property->getValue($model);

        $this->assertContains('title', $allowedFields);
        $this->assertContains('slug', $allowedFields);
        $this->assertContains('file_path', $allowedFields);
        $this->assertNotContains('blocks', $allowedFields);
        $this->assertNotContains('custom_css', $allowedFields);
        $this->assertNotContains('content', $allowedFields);
    }

    public function testInsertPersistsNewColumns(): void
    {
        $model = new LandingPageModel();

        $data = [
            'title'     => 'Test Landing Page',
            'slug'      => 'test-landing-page',
            'file_path' => 'landing_pages/test-landing-page',
        ];

        $id = $model->insert($data);
        $this->assertNotFalse($id);

        $page = $model->findBySlug('test-landing-page');
        $this->assertNotNull($page);
        $this->assertSame('Test Landing Page', $page['title']);
        $this->assertSame('test-landing-page', $page['slug']);
        $this->assertSame('landing_pages/test-landing-page', $page['file_path']);
    }

    public function testInsertContentColumnIsIgnored(): void
    {
        $model = new LandingPageModel();

        $data = [
            'title'   => 'Ignored Content Page',
            'slug'    => 'ignored-content-page',
            'content' => 'This should be ignored by mass assignment protection',
        ];

        $id = $model->insert($data);
        $this->assertNotFalse($id);

        $page = $model->find($id);
        $this->assertNotNull($page);

        $this->assertArrayNotHasKey('content', $page);
    }

    public function testFilePathAcceptsNull(): void
    {
        $model = new LandingPageModel();

        $data = [
            'title' => 'No File Path Page',
            'slug'  => 'no-file-path',
        ];

        $id = $model->insert($data);
        $this->assertNotFalse($id);

        $page = $model->find($id);
        $this->assertNotNull($page);
        $this->assertNull($page['file_path']);
    }

    public function testDuplicateSlugIsRejected(): void
    {
        $model = new LandingPageModel();

        $data = [
            'title'     => 'First Page',
            'slug'      => 'duplicate-slug',
            'file_path' => 'landing_pages/duplicate-slug',
        ];

        $id = $model->insert($data);
        $this->assertNotFalse($id);

        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);

        $model->insert([
            'title'     => 'Second Page',
            'slug'      => 'duplicate-slug',
            'file_path' => 'landing_pages/duplicate-slug-2',
        ]);
    }
}
