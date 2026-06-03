<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\HTTP\ResponseInterface;
use App\Filters\BearerTokenFilter;

class BearerTokenFilterTest extends \CodeIgniter\Test\CIUnitTestCase
{
    private BearerTokenFilter $filter;
    private string $originalKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new BearerTokenFilter();
        $this->originalKey = getenv('encryption.key') ?: '';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('encryption.key=' . $this->originalKey);
    }

    public function testNoAuthorizationHeaderReturns401(): void
    {
        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );

        $result = $this->filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertNull($body['data']);
        $this->assertEquals('Unauthorized', $body['errors']);
    }

    public function testWrongTokenReturns401(): void
    {
        putenv('encryption.key=hex2bin:expected-key');

        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );
        $request->setHeader('Authorization', 'Bearer hex2bin:wrong-key');

        $result = $this->filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertEquals('Unauthorized', $body['errors']);
    }

    public function testCorrectTokenReturnsNull(): void
    {
        putenv('encryption.key=hex2bin:correct-key');

        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );
        $request->setHeader('Authorization', 'Bearer hex2bin:correct-key');

        $result = $this->filter->before($request);

        $this->assertNull($result);
    }

    public function testBearerPrefixWithEmptyTokenReturns401(): void
    {
        putenv('encryption.key=hex2bin:some-key');

        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );
        $request->setHeader('Authorization', 'Bearer ');

        $result = $this->filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    public function testEncryptionKeyNotSetReturns401(): void
    {
        putenv('encryption.key');

        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );
        $request->setHeader('Authorization', 'Bearer hex2bin:some-key');

        $result = $this->filter->before($request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(401, $result->getStatusCode());
    }

    public function testResponseHasCorrectJsonEnvelope(): void
    {
        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );

        $result = $this->filter->before($request);
        $body = json_decode($result->getBody(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('errors', $body);
    }

    public function testLogWrittenOnRejection(): void
    {
        $logger = new \CodeIgniter\Test\TestLogger(new \Config\Logger());
        \Config\Services::injectMock('logger', $logger);

        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );

        $this->filter->before($request);

        $this->assertTrue(
            \CodeIgniter\Test\TestLogger::didLog('warning', '[api.auth.401]', false)
        );
    }

    public function testAfterMethodDoesNothing(): void
    {
        $request = new IncomingRequest(
            new \Config\App(),
            new URI('http://localhost/api/lp/list'),
            'php://input',
            new UserAgent()
        );
        $response = service('response');

        $this->filter->after($request, $response);

        // No exception means it worked; no return type means void
        $this->assertTrue(true);
    }
}
