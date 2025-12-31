<?php

namespace Illuminate\Tests\Auth;


use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieFactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Session\Session as SessionContract;
use Illuminate\Http\Request as IlluminateRequest;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;


class AuthManagerTest extends TestCase
{
    protected $container;

    protected $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;

        $config = new ConfigRepository([
            'auth' => [
                'defaults' => ['guard' => 'web'],
                'guards' => [],
                'providers' => [],
            ],
            'hashing' => ['rehash_on_login' => true],
            'app' => ['key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='],
        ]);

        $this->container->instance('config', $config);

        // Provide basic bindings used by createSessionDriver
        $this->container->instance('session.store', m::mock(SessionContract::class));
        $this->container->instance('cookie', m::mock(CookieFactoryContract::class));
        $this->container->instance('events', m::mock(DispatcherContract::class));
        $this->container->instance('request', IlluminateRequest::create('/'));

        $this->auth = new AuthManager($this->container);
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_guard_throws_when_guard_not_defined()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Auth guard [missing] is not defined.');

        $this->auth->guard('missing');
    }

    public function test_guard_throws_when_driver_not_defined_for_guard()
    {
        $this->container['config']->set('auth.guards.foo', ['driver' => 'nope']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Auth driver [nope] for guard [foo] is not defined.');

        $this->auth->guard('foo');
    }

    public function test_create_session_driver_returns_session_guard_and_is_cached()
    {
        $mockProvider = m::mock(UserProvider::class);

        $this->auth->provider('users', function () use ($mockProvider) {
            return $mockProvider;
        });

        // Provide the provider config so createUserProvider will call our custom provider creator
        $this->container['config']->set('auth.providers.users', ['driver' => 'users']);

        $this->container['config']->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);

        $guard = $this->auth->guard('web');

        $this->assertInstanceOf(SessionGuard::class, $guard);
        $this->assertTrue($this->auth->hasResolvedGuards());

        // Should return cached instance
        $this->assertSame($guard, $this->auth->guard('web'));
    }

    public function test_extend_custom_driver_is_used()
    {
        $mockGuard = new \stdClass;

        $this->auth->extend('mydriver', function ($app, $name, $config) use ($mockGuard) {
            return $mockGuard;
        });

        $this->container['config']->set('auth.guards.custom', ['driver' => 'mydriver']);

        $resolved = $this->auth->guard('custom');

        $this->assertSame($mockGuard, $resolved);
    }

    public function test_via_request_registers_request_guard()
    {
        $this->auth->viaRequest('api', function ($request) {
            return null;
        });

        $this->container['config']->set('auth.guards.api', ['driver' => 'api']);

        $guard = $this->auth->guard('api');

        $this->assertInstanceOf(RequestGuard::class, $guard);
    }

    public function test_should_use_updates_default_driver_and_user_resolver()
    {
        $user = (object) ['id' => 123];

        $mockGuard = m::mock();
        $mockGuard->shouldReceive('user')->andReturn($user);

        $this->auth->extend('secondary', function () use ($mockGuard) {
            return $mockGuard;
        });

        $this->container['config']->set('auth.guards.secondary', ['driver' => 'secondary']);

        $this->auth->shouldUse('secondary');

        $this->assertSame('secondary', $this->container['config']->get('auth.defaults.guard'));

        $resolver = $this->auth->userResolver();

        $this->assertSame($user, $resolver());
    }

    public function test_resolve_users_using_replaces_resolver()
    {
        $this->auth->resolveUsersUsing(function () {
            return 'custom-user';
        });

        $this->assertSame('custom-user', ($this->auth->userResolver())());
    }

    public function test_has_resolved_guards_and_forget_guards_behavior()
    {
        $this->auth->extend('once', function () {
            return new \stdClass;
        });

        $this->container['config']->set('auth.guards.once', ['driver' => 'once']);

        $first = $this->auth->guard('once');

        $this->assertTrue($this->auth->hasResolvedGuards());

        $this->auth->forgetGuards();

        $this->assertFalse($this->auth->hasResolvedGuards());

        $second = $this->auth->guard('once');

        $this->assertNotSame($first, $second);
    }

    public function test_magic_call_proxies_to_default_guard()
    {
        $mock = m::mock();
        $mock->shouldReceive('foo')->once()->andReturn('bar');

        $this->auth->extend('default', function () use ($mock) {
            return $mock;
        });

        // Set default guard to the one backed by our extension
        $this->container['config']->set('auth.defaults.guard', 'default');
        $this->container['config']->set('auth.guards.default', ['driver' => 'default']);

        $this->assertSame('bar', $this->auth->foo());
    }
}
