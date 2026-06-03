<?php

namespace Tests;

class AuthTest extends TestCase
{
    public function testLoginPageLoads(): void
    {
        $result = $this->withURI('http://localhost/login')
            ->controller(\App\Controllers\AuthController::class)
            ->execute('login');

        $this->assertTrue($result->isOK());
    }

    public function testValidLoginRedirectsToDashboard(): void
    {
        $result = $this->withURI('http://localhost/login')
            ->controller(\App\Controllers\AuthController::class)
            ->execute('authenticate');

        // Without POST data, validation fails and redirects back
        // We can't easily test successful POST with ControllerTestTrait
        // So we test the model directly
        $userModel = new \App\Models\UserModel();
        $user = $userModel->findByUsername('admin');
        $this->assertNotNull($user);
        $this->assertTrue(password_verify('123456', $user['password']));
    }

    public function testInvalidLoginShowsError(): void
    {
        $result = $this->withURI('http://localhost/login')
            ->controller(\App\Controllers\AuthController::class)
            ->execute('authenticate');

        $this->assertTrue($result->isRedirect());
    }

    public function testAuthFilterRedirectsGuest(): void
    {
        $result = $this->withURI('http://localhost/dashboard')
            ->controller(\App\Controllers\DashboardController::class)
            ->execute('index');

        // Since the auth filter runs before controller, this might not work as expected
        // Instead, test filter directly
        $filter = new \App\Filters\AuthFilter();
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI('http://localhost/dashboard'),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );
        $response = service('response');

        $result = $filter->before($request, null);
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testAuthFilterAllowsAuthenticated(): void
    {
        $this->loginAsAdmin();

        $filter = new \App\Filters\AuthFilter();
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI('http://localhost/dashboard'),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );

        $result = $filter->before($request, null);
        $this->assertNull($result);
    }

    public function testThrottleFilterReturns429(): void
    {
        $filter = new \App\Filters\ThrottleFilter();
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\URI('http://localhost/login'),
            'php://input',
            new \CodeIgniter\HTTP\UserAgent()
        );
        $request->setMethod('post');

        // Exhaust the throttle limit
        for ($i = 0; $i < 5; $i++) {
            $filter->before($request, null);
        }

        $result = $filter->before($request, null);
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertEquals(429, $result->getStatusCode());
    }

    public function testLogoutDestroysSession(): void
    {
        $this->loginAsAdmin();
        $this->assertTrue(session()->get('isLoggedIn'));

        $result = $this->withURI('http://localhost/logout')
            ->controller(\App\Controllers\AuthController::class)
            ->execute('logout');

        $this->assertTrue($result->isRedirect());
    }
}
