<?php

use Mockery as m;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\ApplicationTrait;

class FoundationApplicationAuthenticationTest extends PHPUnit_Framework_TestCase
{
    use ApplicationTrait;

    /**
     * @var Mockery
     */
    protected $auth;

    public function setUp()
    {
        $this->app = m::mock(Application::class);
        $this->auth = m::mock(AuthManager::class);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testSeeIsAuthenticated()
    {
        $this->auth->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->app->shouldReceive('make')
            ->once()
            ->withArgs(['auth'])
            ->andReturn($this->auth);

        $this->seeIsAuthenticated();
    }

    public function testDontSeeIsAuthenticated()
    {
        $this->auth->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->app->shouldReceive('make')
            ->once()
            ->withArgs(['auth'])
            ->andReturn($this->auth);

        $this->dontSeeIsAuthenticated();
    }

    public function testSeeIsAuthenticatedAs()
    {
        $this->auth->shouldReceive('user')
            ->once()
            ->andReturn('User');

        $this->app->shouldReceive('make')
            ->once()
            ->withArgs(['auth'])
            ->andReturn($this->auth);

        $this->seeIsAuthenticatedAs('User');
    }
}
