<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;

class AuthenticateMiddlewareTest extends PHPUnit_Framework_TestCase
{
    protected $auth;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        $container = Container::setInstance(new Container);

        $this->auth = new AuthManager($container);

        $container->singleton('config', function () {
            return $this->createConfig();
        });
    }

    public function testDefaultUnauthenticatedThrows()
    {
        $this->setExpectedException(AuthenticationException::class);

        $this->registerAuthDriver('default', false);

        $this->authenticate();
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
        $this->setExpectedException(AuthenticationException::class);

        $this->registerAuthDriver('default', false);

        $this->registerAuthDriver('secondary', false);

        $this->authenticate('default', 'secondary');
    }

    public function testMultipleDriversAuthenticatedUdatesDefault()
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
        }, m::mock(Request::class));
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

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new Authenticate($this->auth))->handle($request, $next, ...$guards);

        $this->assertSame($request, $nextParam);
    }
}
