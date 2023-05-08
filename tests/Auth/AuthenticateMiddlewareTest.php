<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\RequestGuard;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthenticateMiddlewareTest extends TestCase
{
    protected $auth;

    protected function setUp(): void
    {
        $container = Container::setInstance(new Container);

        $this->auth = new AuthManager($container);

        $container->singleton('config', function () {
            return $this->createConfig();
        });
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) Authenticate::using('foo');
        $this->assertSame('Illuminate\Auth\Middleware\Authenticate:foo', $signature);

        $signature = (string) Authenticate::using('foo', 'bar');
        $this->assertSame('Illuminate\Auth\Middleware\Authenticate:foo,bar', $signature);

        $signature = (string) Authenticate::using('foo', 'bar', 'baz');
        $this->assertSame('Illuminate\Auth\Middleware\Authenticate:foo,bar,baz', $signature);
    }

    public function testItCanGenerateDefinitionViaStaticMethodForBasic()
    {
        $signature = (string) AuthenticateWithBasicAuth::using('guard');
        $this->assertSame('Illuminate\Auth\Middleware\AuthenticateWithBasicAuth:guard', $signature);

        $signature = (string) AuthenticateWithBasicAuth::using('guard', 'field');
        $this->assertSame('Illuminate\Auth\Middleware\AuthenticateWithBasicAuth:guard,field', $signature);

        $signature = (string) AuthenticateWithBasicAuth::using(field: 'field');
        $this->assertSame('Illuminate\Auth\Middleware\AuthenticateWithBasicAuth:,field', $signature);
    }

    public function testDefaultUnauthenticatedThrows()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $this->registerAuthDriver('default', false);

        $this->authenticate();
    }

    public function testDefaultUnauthenticatedThrowsWithGuards()
    {
        try {
            $this->registerAuthDriver('default', false);

            $this->authenticate('default');
        } catch (AuthenticationException $e) {
            $this->assertContains('default', $e->guards());

            return;
        }

        $this->fail();
    }

    public function testDefaultAuthenticatedKeepsDefaultDriver()
    {
        $driver = $this->registerAuthDriver('default', true);

        $this->authenticate();

        $this->assertSame($driver, $this->auth->guard());
    }

    public function testSecondaryAuthenticatedUpdatesDefaultDriver()
    {
        $this->registerAuthDriver('default', false);

        $secondary = $this->registerAuthDriver('secondary', true);

        $this->authenticate('secondary');

        $this->assertSame($secondary, $this->auth->guard());
    }

    public function testMultipleDriversUnauthenticatedThrows()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $this->registerAuthDriver('default', false);

        $this->registerAuthDriver('secondary', false);

        $this->authenticate('default', 'secondary');
    }

    public function testMultipleDriversUnauthenticatedThrowsWithGuards()
    {
        $expectedGuards = ['default', 'secondary'];

        try {
            $this->registerAuthDriver('default', false);

            $this->registerAuthDriver('secondary', false);

            $this->authenticate(...$expectedGuards);
        } catch (AuthenticationException $e) {
            $this->assertEquals($expectedGuards, $e->guards());

            return;
        }

        $this->fail();
    }

    public function testMultipleDriversAuthenticatedUpdatesDefault()
    {
        $this->registerAuthDriver('default', false);

        $secondary = $this->registerAuthDriver('secondary', true);

        $this->authenticate('default', 'secondary');

        $this->assertSame($secondary, $this->auth->guard());
    }

    /**
     * Create a new config repository instance.
     *
     * @return \Illuminate\Config\Repository
     */
    protected function createConfig()
    {
        return new Config([
            'auth' => [
                'defaults' => ['guard' => 'default'],
                'guards' => [
                    'default' => ['driver' => 'default'],
                    'secondary' => ['driver' => 'secondary'],
                ],
            ],
        ]);
    }

    /**
     * Create and register a new auth driver with the auth manager.
     *
     * @param  string  $name
     * @param  bool  $authenticated
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function registerAuthDriver($name, $authenticated)
    {
        $driver = $this->createAuthDriver($authenticated);

        $this->auth->extend($name, function () use ($driver) {
            return $driver;
        });

        return $driver;
    }

    /**
     * Create a new auth driver.
     *
     * @param  bool  $authenticated
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function createAuthDriver($authenticated)
    {
        return new RequestGuard(function () use ($authenticated) {
            return $authenticated ? new stdClass : null;
        }, m::mock(Request::class), m::mock(EloquentUserProvider::class));
    }

    /**
     * Call the authenticate middleware with the given guards.
     *
     * @param  string  ...$guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate(...$guards)
    {
        $request = m::mock(Request::class);

        $request->shouldReceive('expectsJson')->andReturn(false);

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new Authenticate($this->auth))->handle($request, $next, ...$guards);

        $this->assertSame($request, $nextParam);
    }
}
