<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class TestCase extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed      = \App\Database\Seeds\AdminSeeder::class;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function loginAsAdmin(): void
    {
        session()->set([
            'isLoggedIn' => true,
            'user_id'    => 1,
            'username'   => 'admin',
        ]);
    }
}
