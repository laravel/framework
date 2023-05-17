<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Middleware\SetAuthenticationGuard;
use Illuminate\Auth\RequestGuard;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class SetAuthenticationGuardMiddlewareTest extends TestCase
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

    public function testSetDefaultGuardOfAuth()
    {
        $default = $this->registerAuthDriver('default');
        $secondary = $this->registerAuthDriver('secondary');

        $this->assertSame($default, $this->auth->guard());

        $this->setAuthenticationGuard('secondary');

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
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function registerAuthDriver($name)
    {
        $driver = new RequestGuard(
            fn () => new stdClass,
            m::mock(Request::class),
            m::mock(EloquentUserProvider::class)
        );

        $this->auth->extend($name, fn () => $driver);

        return $driver;
    }
    /**
     * Call the "set authentication guard" middleware with the given guard.
     *
     * @param  string  $guard
     * @return void
     */
    protected function setAuthenticationGuard($guard)
    {
        $request = m::mock(Request::class);

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new SetAuthenticationGuard($this->auth))->handle($request, $next, $guard);

        $this->assertSame($request, $nextParam);
    }
}
