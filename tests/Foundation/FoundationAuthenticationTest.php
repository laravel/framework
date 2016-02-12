<?php

use Mockery as m;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;

class FoundationAuthenticationTest extends PHPUnit_Framework_TestCase
{
    use InteractsWithAuthentication;

    /**
     * @var \Mockery
     */
    protected $app;

    /**
     * @var \Mockery
     */
    protected $auth;

    /**
     * @var \Mockery
     */
    protected $guard;

    public function setUp()
    {
        $this->guard = m::mock(Guard::class);

        $this->auth = m::mock(AuthManager::class);
        $this->auth->shouldReceive('guard')
            ->once()
            ->andReturn($this->guard);

        $this->app = m::mock(Application::class);
        $this->app->shouldReceive('make')
            ->once()
            ->withArgs(['auth'])
            ->andReturn($this->auth);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testSeeIsAuthenticated()
    {
        $this->guard->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->seeIsAuthenticated();
    }

    public function testDontSeeIsAuthenticated()
    {
        $this->guard->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->dontSeeIsAuthenticated();
    }

    public function testSeeIsAuthenticatedAs()
    {
        $this->guard->shouldReceive('user')
            ->once()
            ->andReturn('Someone');

        $this->seeIsAuthenticatedAs('Someone');
    }
}
