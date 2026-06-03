<?php

namespace Tests;

class LandingPagesTest extends TestCase
{
    protected function setUp(): void
    {
        \Config\Services::reset();
        parent::setUp();
        $this->loginAsAdmin();
    }

    public function testCreateLandingPage(): void
    {
        $model = new \App\Models\LandingPageModel();
        $data = [
            'title'     => 'Test Page',
            'slug'      => 'test-page',
            'file_path' => null,
        ];

        $id = $model->insert($data);
        $this->assertNotFalse($id);

        $page = $model->find($id);
        $this->assertEquals('Test Page', $page['title']);
        $this->assertEquals('test-page', $page['slug']);
    }

    public function testDuplicateSlugShowsError(): void
    {
        $model = new \App\Models\LandingPageModel();
        $model->insert([
            'title'     => 'First Page',
            'slug'      => 'duplicate-slug',
            'file_path' => null,
        ]);

        try {
            $model->insert([
                'title'     => 'Second Page',
                'slug'      => 'duplicate-slug',
                'file_path' => null,
            ]);
            $this->fail('Expected exception for duplicate slug');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testFindBySlug(): void
    {
        $model = new \App\Models\LandingPageModel();
        $model->insert([
            'title'     => 'Find Me',
            'slug'      => 'find-me',
            'file_path' => null,
        ]);

        $page = $model->findBySlug('find-me');
        $this->assertNotNull($page);
        $this->assertEquals('Find Me', $page['title']);

        $missing = $model->findBySlug('nonexistent');
        $this->assertNull($missing);
    }

    public function testEditAndUpdate(): void
    {
        $model = new \App\Models\LandingPageModel();
        $id = $model->insert([
            'title'     => 'Original',
            'slug'      => 'original-slug',
            'file_path' => null,
        ]);

        $page = $model->find($id);
        $this->assertEquals('Original', $page['title']);

        $model->update($id, ['title' => 'Updated']);
        $updated = $model->find($id);
        $this->assertEquals('Updated', $updated['title']);
    }

    public function testDeleteLandingPage(): void
    {
        $model = new \App\Models\LandingPageModel();
        $id = $model->insert([
            'title'     => 'To Delete',
            'slug'      => 'to-delete',
            'file_path' => null,
        ]);

        $this->assertNotNull($model->find($id));
        $model->delete($id);
        $this->assertNull($model->find($id));
    }

    public function testCreateViewRendersAssetsField(): void
    {
        $result = $this->withURI('http://localhost/landing-pages/create')
            ->controller(\App\Controllers\LandingPagesController::class)
            ->execute('create');

        $this->assertTrue($result->isOK());
        $body = $result->getBody();
        $this->assertStringContainsString('name="assets[]"', $body);
        $this->assertStringContainsString('id="assets"', $body);
        $this->assertStringContainsString('accept=".jpg,.jpeg,.png,.webp,.svg,.gif,.mp4,.webm,.ogg"', $body);
        $this->assertStringContainsString('Assets <small>', $body);
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

    public function testStoreLandingPageWithAssets(): void
    {
        $slug = 'assets-test-page';
        $dir = WRITEPATH . 'landing_pages/' . $slug;
        if (is_dir($dir)) {
            $this->removeTestDir($dir);
        }

        $htmlFile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($htmlFile, '<html><body><form id="lead-form"></form></body></html>');
        $imgFile = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($imgFile, 'fake image data');
        $videoFile = tempnam(sys_get_temp_dir(), 'video');
        file_put_contents($videoFile, 'fake video data');

        $result = $this->withURI('http://localhost/landing-pages')
            ->controller(\App\Controllers\LandingPagesController::class);

        // Populate request method and post/request globals
        $_POST = [
            'title' => 'Assets Test Page',
            'slug'  => $slug,
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        // Instantiate and set mock FileCollection using Reflection
        $mockFiles = [
            'index_html' => new MockUploadedFile($htmlFile, 'index.html', 'text/html', filesize($htmlFile), UPLOAD_ERR_OK),
            'assets'     => [
                new MockUploadedFile($imgFile, 'test_image.png', 'image/png', filesize($imgFile), UPLOAD_ERR_OK),
                new MockUploadedFile($videoFile, 'test_video.mp4', 'video/mp4', filesize($videoFile), UPLOAD_ERR_OK),
            ],
        ];
        $fileCollection = new MockFileCollection($mockFiles);

        $ref = new \ReflectionClass($this->request);
        $prop = $ref->getProperty('files');
        $prop->setAccessible(true);
        $prop->setValue($this->request, $fileCollection);

        // Inject the mocked request into the Services container
        \Config\Services::injectMock('request', $this->request);

        $result = $result->execute('store');

        $this->assertTrue($result->isRedirect());

        $this->assertDirectoryExists($dir . '/assets');
        $this->assertFileExists($dir . '/index.html');
        $this->assertFileExists($dir . '/assets/test_image.png');
        $this->assertFileExists($dir . '/assets/test_video.mp4');

        $this->removeTestDir($dir);

        // Delete from database
        $model = new \App\Models\LandingPageModel();
        $page = $model->findBySlug($slug);
        if ($page) {
            $model->delete($page['id']);
        }

        // Reset mock request
        \Config\Services::injectMock('request', null);

        // Clean up temp files
        @unlink($htmlFile);
        @unlink($imgFile);
        @unlink($videoFile);
    }

    public function testStoreLandingPageValidationRejectsTooLargeAsset(): void
    {
        $slug = 'too-large-page';
        $htmlFile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($htmlFile, '<html><body><form id="lead-form"></form></body></html>');
        $largeFile = tempnam(sys_get_temp_dir(), 'large');
        file_put_contents($largeFile, 'fake large data');

        $result = $this->withURI('http://localhost/landing-pages')
            ->controller(\App\Controllers\LandingPagesController::class);

        $_POST = [
            'title' => 'Too Large Page',
            'slug'  => $slug,
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        // Size set to 51.2MB (which is > 50MB = 51200KB)
        $mockFiles = [
            'index_html' => new MockUploadedFile($htmlFile, 'index.html', 'text/html', filesize($htmlFile), UPLOAD_ERR_OK),
            'assets'     => [
                new MockUploadedFile($largeFile, 'large_video.mp4', 'video/mp4', 51201 * 1024, UPLOAD_ERR_OK),
            ],
        ];
        $fileCollection = new MockFileCollection($mockFiles);

        $ref = new \ReflectionClass($this->request);
        $prop = $ref->getProperty('files');
        $prop->setAccessible(true);
        $prop->setValue($this->request, $fileCollection);

        \Config\Services::injectMock('request', $this->request);

        $result = $result->execute('store');

        $this->assertTrue($result->isRedirect());
        $errors = session()->get('errors');
        $this->assertArrayHasKey('assets', $errors);

        \Config\Services::injectMock('request', null);

        @unlink($htmlFile);
        @unlink($largeFile);
    }

    public function testStoreLandingPageValidationRejectsInvalidAssetFormat(): void
    {
        $slug = 'invalid-format-page';
        $htmlFile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($htmlFile, '<html><body><form id="lead-form"></form></body></html>');
        $badFile = tempnam(sys_get_temp_dir(), 'bad');
        file_put_contents($badFile, 'fake bad data');

        $result = $this->withURI('http://localhost/landing-pages')
            ->controller(\App\Controllers\LandingPagesController::class);

        $_POST = [
            'title' => 'Invalid Format Page',
            'slug'  => $slug,
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        // Unallowed extension .zip
        $mockFiles = [
            'index_html' => new MockUploadedFile($htmlFile, 'index.html', 'text/html', filesize($htmlFile), UPLOAD_ERR_OK),
            'assets'     => [
                new MockUploadedFile($badFile, 'malicious.zip', 'application/zip', filesize($badFile), UPLOAD_ERR_OK),
            ],
        ];
        $fileCollection = new MockFileCollection($mockFiles);

        $ref = new \ReflectionClass($this->request);
        $prop = $ref->getProperty('files');
        $prop->setAccessible(true);
        $prop->setValue($this->request, $fileCollection);

        \Config\Services::injectMock('request', $this->request);

        $result = $result->execute('store');

        $this->assertTrue($result->isRedirect());
        $errors = session()->get('errors');
        $this->assertArrayHasKey('assets', $errors);

        \Config\Services::injectMock('request', null);

        @unlink($htmlFile);
        @unlink($badFile);
    }

    public function testStoreLandingPageWithEmptyOptionalFiles(): void
    {
        $slug = 'empty-optional-page';
        $dir = WRITEPATH . 'landing_pages/' . $slug;
        if (is_dir($dir)) {
            $this->removeTestDir($dir);
        }

        $htmlFile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($htmlFile, '<html><body><form id="lead-form"></form></body></html>');

        $result = $this->withURI('http://localhost/landing-pages')
            ->controller(\App\Controllers\LandingPagesController::class);

        $_POST = [
            'title' => 'Empty Optional Page',
            'slug'  => $slug,
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        // Optional files are not uploaded (UPLOAD_ERR_NO_FILE)
        $mockFiles = [
            'index_html' => new MockUploadedFile($htmlFile, 'index.html', 'text/html', filesize($htmlFile), UPLOAD_ERR_OK),
            'style_css'  => new MockUploadedFile('', '', '', 0, UPLOAD_ERR_NO_FILE),
            'app_js'     => new MockUploadedFile('', '', '', 0, UPLOAD_ERR_NO_FILE),
            'assets'     => [
                new MockUploadedFile('', '', '', 0, UPLOAD_ERR_NO_FILE)
            ],
        ];
        $fileCollection = new MockFileCollection($mockFiles);

        $ref = new \ReflectionClass($this->request);
        $prop = $ref->getProperty('files');
        $prop->setAccessible(true);
        $prop->setValue($this->request, $fileCollection);

        \Config\Services::injectMock('request', $this->request);

        $result = $result->execute('store');

        $this->assertTrue($result->isRedirect());
        $errors = session()->get('errors');
        $this->assertEmpty($errors ?? []);

        \Config\Services::injectMock('request', null);
        @unlink($htmlFile);
        if (is_dir($dir)) {
            $this->removeTestDir($dir);
        }
    }
}


class MockUploadedFile extends \CodeIgniter\HTTP\Files\UploadedFile
{
    protected ?string $mockMimeType = null;

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $size = null, ?int $error = null, ?string $clientPath = null)
    {
        parent::__construct($path, $originalName, $mimeType, $size, $error, $clientPath);
        $this->mockMimeType = $mimeType;
    }

    public function getMimeType(): string
    {
        return $this->mockMimeType ?? parent::getMimeType();
    }

    public function getSize(): false|int
    {
        return $this->size ?? false;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function move(string $targetPath, ?string $name = null, bool $overwrite = false): bool
    {
        $targetPath = rtrim($targetPath, '/') . '/';
        if (! is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
        $name ??= $this->getName();
        return copy($this->path, $targetPath . $name);
    }
}

class MockFileCollection extends \CodeIgniter\HTTP\Files\FileCollection
{
    public function __construct(array $files)
    {
        $this->files = $files;
    }

    protected function populateFiles(): void
    {
        // Prevent reading from global $_FILES
    }

    public function getFile(string $name): ?\CodeIgniter\HTTP\Files\UploadedFile
    {
        if (isset($this->files[$name])) {
            return is_array($this->files[$name]) ? reset($this->files[$name]) : $this->files[$name];
        }
        return new MockUploadedFile('', '', '', 0, UPLOAD_ERR_NO_FILE);
    }

    public function getFileMultiple(string $name): ?array
    {
        if (isset($this->files[$name])) {
            return is_array($this->files[$name]) ? $this->files[$name] : [$this->files[$name]];
        }
        return [new MockUploadedFile('', '', '', 0, UPLOAD_ERR_NO_FILE)];
    }
}

