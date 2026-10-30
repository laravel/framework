<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Config\Repository as ConfigRepository;
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

    /**
     * @return \Illuminate\Contracts\Auth\Guard
     */
    protected function getRealGuard(?Authenticatable $user = null)
    {
        $guard = new class($user) implements Guard
        {
            public function __construct(protected ?Authenticatable $user)
            {
            }

            public function check()
            {
                return ! is_null($this->user);
            }

            public function guest()
            {
                return is_null($this->user);
            }

            public function user()
            {
                return $this->user;
            }

            public function id()
            {
                return $this->user?->getAuthIdentifier();
            }

            public function validate(array $credentials = [])
            {
                return false;
            }

            public function hasUser()
            {
                return ! is_null($this->user);
            }

            public function setUser(Authenticatable $user)
            {
                $this->user = $user;

                return $this;
            }
        };

        $this->app = new Application;
        $this->app['config'] = new ConfigRepository([
            'auth' => [
                'defaults' => ['guard' => 'web'],
                'guards' => ['web' => ['driver' => 'test']],
            ],
        ]);

        $auth = new AuthManager($this->app);
        $auth->extend('test', fn () => $guard);
        $this->app->instance('auth', $auth);

        return $guard;
    }

    public function testAssertAuthenticated()
    {
        $this->getRealGuard(new GenericUser(['id' => 1]));

        $this->assertAuthenticated();
    }

    public function testAssertGuest()
    {
        $this->getRealGuard();

        $this->assertGuest();
    }

    public function testAssertAuthenticatedAs()
    {
        $this->getRealGuard(new GenericUser(['id' => 1]));

        $user = new GenericUser(['id' => 1]);

        $this->assertAuthenticatedAs($user);
    }

    protected function setupProvider(array $credentials)
    {
        $user = new GenericUser([]);

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
