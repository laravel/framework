<?php

use Mockery as m;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;

class FoundationAuthenticationTest extends PHPUnit_Framework_TestCase
{
    use InteractsWithAuthentication;

    /**
     * @var \Mockery
     */
    protected $app;

    /**
     * @return array
     */
    protected $credentials = [
        'email' => 'someone@laravel.com',
        'password' => 'secret_password',
    ];

    /**
     * @var \Mockery
     */
    protected function mockGuard()
    {
        $guard = m::mock(Guard::class);

        $auth = m::mock(AuthManager::class);
        $auth->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->app = m::mock(Application::class);
        $this->app->shouldReceive('make')
            ->once()
            ->withArgs(['auth'])
            ->andReturn($auth);

        return $guard;
    }

    public function tearDown()
    {
        m::close();
    }

    public function testSeeIsAuthenticated()
    {
        $this->mockGuard()
            ->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->seeIsAuthenticated();
    }

    public function testDontSeeIsAuthenticated()
    {
        $this->mockGuard()
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->dontSeeIsAuthenticated();
    }

    public function testSeeIsAuthenticatedAs()
    {
        $this->mockGuard()
            ->shouldReceive('user')
            ->once()
            ->andReturn('Someone');

        $this->seeIsAuthenticatedAs('Someone');
    }

    protected function setupProvider(array $credentials)
    {
        $user = m::mock(Authenticatable::class);

        $provider = m::mock(UserProvider::class);

        $provider->shouldReceive('retrieveByCredentials')
            ->with($credentials)
            ->andReturn($user);

        $provider->shouldReceive('validateCredentials')
            ->with($user, $credentials)
            ->andReturn($this->credentials === $credentials);

        $this->mockGuard()
            ->shouldReceive('getProvider')
            ->once()
            ->andReturn($provider);
    }

    public function testSeeCredentials()
    {
        $this->setupProvider($this->credentials);

        $this->seeCredentials($this->credentials);
    }

    public function testDontSeeCredentials()
    {
        $credentials = [
            'email' => 'invalid',
            'password' => 'credentials',
        ];

        $this->setupProvider($credentials);

        $this->dontSeeCredentials($credentials);
    }
}
