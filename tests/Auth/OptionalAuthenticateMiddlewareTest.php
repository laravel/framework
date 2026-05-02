<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Middleware\OptionalAuthenticate;
use Illuminate\Auth\RequestGuard;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class OptionalAuthenticateMiddlewareTest extends TestCase
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
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = OptionalAuthenticate::using('foo');
        $this->assertSame('Illuminate\Auth\Middleware\OptionalAuthenticate:foo', $signature);

        $signature = OptionalAuthenticate::using('foo', 'bar');
        $this->assertSame('Illuminate\Auth\Middleware\OptionalAuthenticate:foo,bar', $signature);
    }

    public function testUnauthenticatedDoesNotThrow()
    {
        $this->registerAuthDriver('default', false);

        $this->optionalAuthenticate('default');
    }

    public function testItThrowsWhenNoGuardIsSpecified()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[optionalAuth]');

        $this->registerAuthDriver('default', false);

        $this->optionalAuthenticate();
    }

    public function testUnauthenticatedWithNamedGuardsDoesNotThrow()
    {
        $this->registerAuthDriver('default', false);
        $this->registerAuthDriver('secondary', false);

        $this->optionalAuthenticate('default', 'secondary');
    }

    public function testAuthenticatedSetsDefaultDriver()
    {
        $driver = $this->registerAuthDriver('default', true);

        $this->optionalAuthenticate('default');

        $this->assertSame($driver, $this->auth->guard());
    }

    public function testSecondaryGuardAuthenticatesWhenDefaultFails()
    {
        $this->registerAuthDriver('default', false);

        $secondary = $this->registerAuthDriver('secondary', true);

        $this->optionalAuthenticate('default', 'secondary');

        $this->assertSame($secondary, $this->auth->guard());
    }

    public function testGuestRequestSetsDefaultGuardToFirstNamedGuardForAuthResolution()
    {
        $this->registerAuthDriver('secondary', false);

        $this->optionalAuthenticate('secondary');

        $this->assertSame('secondary', $this->auth->getDefaultDriver());
    }

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

    protected function registerAuthDriver($name, $authenticated)
    {
        $driver = $this->createAuthDriver($authenticated);

        $this->auth->extend($name, function () use ($driver) {
            return $driver;
        });

        return $driver;
    }

    protected function createAuthDriver($authenticated)
    {
        return new RequestGuard(function () use ($authenticated) {
            return $authenticated ? new stdClass : null;
        }, m::mock(Request::class), m::mock(EloquentUserProvider::class));
    }

    protected function optionalAuthenticate(...$guards)
    {
        $request = m::mock(Request::class);

        $nextParam = null;

        $next = function ($param) use (&$nextParam) {
            $nextParam = $param;
        };

        (new OptionalAuthenticate($this->auth))->handle($request, $next, ...$guards);

        $this->assertSame($request, $nextParam);
    }
}
