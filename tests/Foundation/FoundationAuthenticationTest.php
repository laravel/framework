<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\SessionGuard;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class FoundationAuthenticationTest extends TestCase
{
    use InteractsWithAuthentication;
    use VerifiesDoubles;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
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
     * @return \Illuminate\Contracts\Auth\Guard|\JMac\Testing\DoubleInterface
     */
    protected function mockGuard()
    {
        $guard = Double::for(SessionGuard::class);

        $auth = Double::for(AuthManager::class);
        $auth->expects('guard')->returns($guard);

        $app = Double::for(Application::class, override: true);
        $app->expects('make')->with('auth')->returns($auth);
        $this->app = $app->instance();

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

        $provider = Double::for(UserProvider::class);

        $provider->expects('retrieveByCredentials')->with($credentials)->returns($user);

        $provider->expects('validateCredentials')->with($user, $credentials)->returns($this->credentials === $credentials);

        $this->mockGuard()->expects('getProvider')->returns($provider);
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
