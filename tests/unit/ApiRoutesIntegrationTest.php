<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FilterTestTrait;

class ApiRoutesIntegrationTest extends CIUnitTestCase
{
    use FilterTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFilterTestTrait();
    }

    public function testDashboardStillHasAuthFilter(): void
    {
        $this->assertFilter('dashboard', 'before', 'auth');
    }

    public function testLoginStillHasThrottleFilter(): void
    {
        $this->assertFilter('login', 'before', 'throttle');
    }

    public function testPublicRouteHasNoFilters(): void
    {
        $this->assertNotHasFilters('p/test-slug', 'before');
    }

    public function testLandingPagesAdminRouteStillHasAuthFilter(): void
    {
        $this->assertFilter('landing-pages', 'before', 'auth');
    }

    public function testExistingRoutesNotAffectedByApiFilters(): void
    {
        $this->assertNotFilter('login', 'before', 'bearerToken');
        $this->assertNotFilter('dashboard', 'before', 'bearerToken');
        $this->assertNotFilter('p/public-test', 'before', 'bearerToken');
    }
}
