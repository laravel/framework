<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Mockery;
use PHPUnit\Framework\TestCase;

class FoundationAuthenticationTest extends TestCase
{
    use InteractsWithAuthentication;

    /**
     * @var \Mockery
     */
    protected $app;

    /**
     * @var array
     */
    protected $credentials = [
        'email' => 'someone@laravel.com',
        'password' => 'secret_password',
    ];

    /**
     * @return \Illuminate\Contracts\Auth\Guard|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected function mockGuard()
    {
        $guard = Mockery::mock(Guard::class);

        $auth = Mockery::mock(AuthManager::class);
        $auth->expects('guard')
            ->andReturn($guard);

        $this->app = Mockery::mock(Application::class);
        $this->app->expects('make')
            ->withArgs(['auth'])
            ->andReturn($auth);

        return $guard;
    }

    public function testAssertAuthenticated()
    {
        $this->mockGuard()
            ->expects('check')
            ->andReturn(true);

        $this->assertAuthenticated();
    }

    public function testAssertGuest()
    {
        $this->mockGuard()
            ->expects('check')
            ->andReturn(false);

        $this->assertGuest();
    }

    public function testAssertAuthenticatedAs()
    {
        $expected = Mockery::mock(Authenticatable::class);
        $expected->expects('getAuthIdentifier')
            ->andReturn('1');

        $this->mockGuard()
            ->expects('user')
            ->andReturn($expected);

        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthIdentifier')
            ->andReturn('1');

        $this->assertAuthenticatedAs($user);
    }

    protected function setupProvider(array $credentials)
    {
        $user = Mockery::mock(Authenticatable::class);

        $provider = Mockery::mock(UserProvider::class);

        $provider->expects('retrieveByCredentials')
            ->with($credentials)
            ->andReturn($user);

        $provider->expects('validateCredentials')
            ->with($user, $credentials)
            ->andReturn($this->credentials === $credentials);

        $this->mockGuard()
            ->expects('getProvider')
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
