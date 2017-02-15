<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;

class FoundationAuthenticationTest extends TestCase
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

    public function testAssertAuthenticated()
    {
        $this->mockGuard()
            ->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->assertAuthenticated();
    }

    public function testAssertGuest()
    {
        $this->mockGuard()
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->assertGuest();
    }

    public function testAssertAuthenticatedAs()
    {
        $expected = m::mock(Authenticatable::class);
        $expected->shouldReceive('getAuthIdentifier')
            ->andReturn('1');

        $this->mockGuard()
            ->shouldReceive('user')
            ->once()
            ->andReturn($expected);

        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')
            ->andReturn('1');

        $this->assertAuthenticatedAs($user);
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

    public function testAssertCredentials()
    {
        $this->setupProvider($this->credentials);

        $this->assertCredentials($this->credentials);
    }

    public function testAssertCredentialsMissing()
    {
        $credentials = [
            'email' => 'invalid',
            'password' => 'credentials',
        ];

        $this->setupProvider($credentials);

        $this->assertInvalidCredentials($credentials);
    }
}
