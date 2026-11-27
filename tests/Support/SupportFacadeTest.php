<?php

namespace Illuminate\Tests\Support;

use ArrayAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Traits\Macroable;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Exceptions\UnknownMethodException;
use JMac\Testing\Integrations\PHPUnit\PHPUnitUnexpectedCallException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class SupportFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
        FacadeStub::setFacadeApplication(null);
    }

    protected function tearDown(): void
    {
        Facade::useMockery();

        parent::tearDown();
    }

    protected function setUpApplication(): ApplicationStub
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        return $app;
    }

    public function testFacadeCallsUnderlyingApplication()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new class
        {
            public function bar()
            {
                return 'baz';
            }
        }]);
        FacadeStub::setFacadeApplication($app);
        $this->assertSame('baz', FacadeStub::bar());
    }

    public function testShouldReceiveReturnsAMockeryMock()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new stdClass]);
        FacadeStub::setFacadeApplication($app);

        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::expects('foo')->with('bar')->andReturn('baz')->getMock());
        $this->assertSame('baz', $app['foo']->foo('bar'));
    }

    public function testSpyReturnsAMockerySpy()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new stdClass]);
        FacadeStub::setFacadeApplication($app);

        $this->assertInstanceOf(MockInterface::class, $spy = FacadeStub::spy());

        FacadeStub::foo();
        $spy->shouldHaveReceived('foo');
    }

    public function testShouldReceiveCanBeCalledTwice()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new stdClass]);
        FacadeStub::setFacadeApplication($app);

        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::expects('foo')->with('bar')->andReturn('baz')->getMock());
        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::expects('foo2')->with('bar2')->andReturn('baz2')->getMock());
        $this->assertSame('baz', $app['foo']->foo('bar'));
        $this->assertSame('baz2', $app['foo']->foo2('bar2'));
    }

    public function testCanBeMockedWithoutUnderlyingInstance()
    {
        FacadeStub::expects('foo')->andReturn('bar');
        $this->assertSame('bar', FacadeStub::foo());
    }

    public function testExpectsReturnsAMockeryMockWithExpectationRequired()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new stdClass]);
        FacadeStub::setFacadeApplication($app);

        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::expects('foo')->with('bar')->andReturn('baz')->getMock());
        $this->assertSame('baz', $app['foo']->foo('bar'));
    }

    public function testUsesMockeryByDefault()
    {
        $this->assertTrue(Facade::usesMockery());

        Facade::useDouble();
        $this->assertFalse(Facade::usesMockery());

        Facade::useMockery();
        $this->assertTrue(Facade::usesMockery());
    }

    public function testSwappedDoubleIsConfiguredByTheFacade()
    {
        $app = $this->setUpApplication();

        FacadeStub::swap($double = Double::for(FacadeStubTarget::class));
        FacadeStub::shouldReceive('foo')->returns('baz');

        $this->assertSame($double, $app['foo']);
        $this->assertSame('baz', FacadeStub::foo());
    }

    public function testShouldHaveReceivedForwardsToMockerySpy()
    {
        $this->setUpApplication();

        $spy = FacadeStub::spy();

        FacadeStub::foo('bar');

        $this->assertInstanceOf(MockInterface::class, $spy);
        FacadeStub::shouldHaveReceived('foo')->with('bar')->once();
        FacadeStub::shouldHaveReceived('foo', ['bar']);
        FacadeStub::shouldNotHaveReceived('foo2');
    }

    public function testShouldNotReceiveForwardsToMockeryMock()
    {
        $this->setUpApplication();

        FacadeStub::shouldReceive('foo');

        $this->assertInstanceOf(Mockery\ExpectationInterface::class, FacadeStub::shouldNotReceive('foo2'));

        FacadeStub::foo();
    }

    public function testShouldReceiveSwapsInADoubleWhenUsingDouble()
    {
        Facade::useDouble();
        $app = $this->setUpApplication();

        FacadeStub::shouldReceive('foo')->with('bar')->returns('baz');

        $this->assertInstanceOf(DoubleInterface::class, $app['foo']);
        $this->assertInstanceOf(FacadeStubTarget::class, $app['foo']);
        $this->assertSame('baz', FacadeStub::foo('bar'));
    }

    public function testShouldReceiveCreatesAStrictDoubleWhenUsingDouble()
    {
        Facade::useDouble();
        $this->setUpApplication();

        FacadeStub::shouldReceive('foo');

        $this->expectException(PHPUnitUnexpectedCallException::class);

        FacadeStub::foo2();
    }

    public function testSpyReturnsADoubleWhenUsingDouble()
    {
        Facade::useDouble();
        $app = $this->setUpApplication();

        $this->assertInstanceOf(DoubleInterface::class, $spy = FacadeStub::spy());
        $this->assertSame($spy, $app['foo']);

        $this->assertNull(FacadeStub::foo());

        $spy->received('foo');
        FacadeStub::shouldHaveReceived('foo');
    }

    public function testShouldReceiveCanBeCalledTwiceWhenUsingDouble()
    {
        Facade::useDouble();
        $app = $this->setUpApplication();

        FacadeStub::expects('foo')->with('bar')->returns('baz');
        FacadeStub::expects('foo2')->with('bar2')->returns('baz2');

        $this->assertSame('baz', $app['foo']->foo('bar'));
        $this->assertSame('baz2', $app['foo']->foo2('bar2'));
    }

    public function testCannotBeMockedWithoutUnderlyingInstanceWhenUsingDouble()
    {
        Facade::useDouble();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not have a resolvable root');

        FacadeStub::expects('foo');
    }

    public function testShouldNotReceiveWhenUsingDouble()
    {
        Facade::useDouble();
        $this->setUpApplication();

        FacadeStub::shouldReceive('foo');
        FacadeStub::shouldNotReceive('foo2');

        FacadeStub::foo();

        $this->expectException(AssertionFailedError::class);

        FacadeStub::foo2();
    }

    public function testShouldHaveReceivedWithArgumentsWhenUsingDouble()
    {
        Facade::useDouble();
        $this->setUpApplication();

        FacadeStub::spy();

        FacadeStub::foo('bar');

        FacadeStub::shouldHaveReceived('foo', ['bar']);
        FacadeStub::shouldHaveReceived('foo')->with('bar')->times(1);
        FacadeStub::shouldNotHaveReceived('foo', ['baz']);
        FacadeStub::shouldNotHaveReceived('foo2');
    }

    public function testShouldHaveReceivedRequiresAMockWhenUsingDouble()
    {
        Facade::useDouble();
        $this->setUpApplication();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be spied or mocked');

        FacadeStub::shouldHaveReceived('foo');
    }

    public function testPartialMockPassesThroughToTheRealInstanceWhenUsingDouble()
    {
        Facade::useDouble();
        $this->setUpApplication();

        FacadeStub::partialMock()->allows('foo2')->returns('baz');

        $this->assertSame('real', FacadeStub::foo());
        $this->assertSame('baz', FacadeStub::foo2());
    }

    public function testDoubleIsReusedAfterClearingResolvedInstancesWhenUsingDouble()
    {
        Facade::useDouble();
        $this->setUpApplication();

        FacadeStub::shouldReceive('foo')->returns('baz');

        Facade::clearResolvedInstances();

        FacadeStub::shouldReceive('foo2')->returns('baz2');

        $this->assertSame('baz', FacadeStub::foo());
        $this->assertSame('baz2', FacadeStub::foo2());
    }

    public function testRootWithReservedMethodNamesCanBeMockedWhenUsingDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTargetWithReservedNames]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::expects('allows')->with('ability')->returns(true);

        $this->assertInstanceOf(FacadeStubTargetWithReservedNames::class, $app['foo']);
        $this->assertTrue(FacadeStub::allows('ability'));

        FacadeStub::shouldHaveReceived('allows')->with('ability');
    }

    public function testSwappedMockeryMockIsConfiguredByTheFacadeWhenUsingDouble()
    {
        Facade::useDouble();
        $app = $this->setUpApplication();

        FacadeStub::swap($mock = Mockery::mock(FacadeStubTarget::class));
        FacadeStub::shouldReceive('foo')->andReturn('baz');

        $this->assertSame($mock, $app['foo']);
        $this->assertSame('baz', FacadeStub::foo());
    }

    public function testForwardedMethodExplainsTheTestingAlternativeWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubForwardingTarget, 'auth');

        $exception = $this->catchRuntimeException(fn () => Auth::shouldReceive('forwarded'));

        $this->assertSame(
            "`Auth::forwarded()` is forwarded through `FacadeStubForwardingTarget::__call()`, so it can't be mocked with Double. Authenticate a real user with `actingAs()` and assert with `assertAuthenticated()` or `assertGuest()`. Or call `Facade::useMockery()` to mock it with Mockery.",
            $exception->getMessage()
        );
        $this->assertInstanceOf(UnknownMethodException::class, $exception->getPrevious());
    }

    public function testForwardedMethodExplainsTheInheritedTestingAlternativeWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubForwardingTarget, 'auth');

        $exception = $this->catchRuntimeException(fn () => FacadeStubExtendingAuth::shouldReceive('forwarded'));

        $this->assertStringContainsString('Authenticate a real user with `actingAs()`', $exception->getMessage());
    }

    public function testForwardedMethodExplainsTheOverriddenTestingAlternativeWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubForwardingTarget);

        $exception = $this->catchRuntimeException(fn () => FacadeStubWithAlternative::shouldReceive('forwarded'));

        $this->assertSame(
            "`FacadeStubWithAlternative::forwarded()` is forwarded through `FacadeStubForwardingTarget::__call()`, so it can't be mocked with Double. Use the real thing. Or call `Facade::useMockery()` to mock it with Mockery.",
            $exception->getMessage()
        );
    }

    public function testForwardedMethodWithoutTestingAlternativeWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubForwardingTarget);

        $exception = $this->catchRuntimeException(fn () => FacadeStub::expects('forwarded'));

        $this->assertSame(
            "`FacadeStub::forwarded()` is forwarded through `FacadeStubForwardingTarget::__call()`, so it can't be mocked with Double. Or call `Facade::useMockery()` to mock it with Mockery.",
            $exception->getMessage()
        );
    }

    public function testReceivedForwardedMethodExplainsTheTestingAlternativeWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubForwardingTarget, 'auth');

        Auth::spy();

        $exception = $this->catchRuntimeException(fn () => Auth::shouldHaveReceived('forwarded'));

        $this->assertStringContainsString('Authenticate a real user with `actingAs()`', $exception->getMessage());
    }

    public function testCloseMatchKeepsTheSuggestionWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubForwardingTarget);

        $this->expectException(UnknownMethodException::class);
        $this->expectExceptionMessage('store');

        FacadeStub::shouldReceive('stor');
    }

    public function testMacroIsExplainedWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubMacroableTarget);

        FacadeStubMacroableTarget::macro('shout', fn () => 'HEY');

        try {
            $exception = $this->catchRuntimeException(fn () => FacadeStub::shouldReceive('shout'));

            $this->assertStringStartsWith("`FacadeStub::shout()` is a macro, so it can't be mocked with Double.", $exception->getMessage());
        } finally {
            FacadeStubMacroableTarget::flushMacros();
        }
    }

    public function testUndeclaredMethodOnMacroOnlyRootIsUnchangedWhenUsingDouble()
    {
        Facade::useDouble();

        $this->setUpApplicationWith(new FacadeStubMacroableTarget);

        $this->expectException(UnknownMethodException::class);

        FacadeStub::shouldReceive('missing');
    }

    protected function setUpApplicationWith(object $root, string $accessor = 'foo'): ApplicationStub
    {
        $app = new ApplicationStub;
        $app->setAttributes([$accessor => $root]);
        FacadeStub::setFacadeApplication($app);

        return $app;
    }

    protected function catchRuntimeException(callable $callback): RuntimeException
    {
        try {
            $callback();
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('Expected a RuntimeException to be thrown.');
    }

    public function testFacadeResolvesAgainAfterClearingSpecific()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => $service = new FacadeTestService]);

        // Resolve for the first time
        FacadeStub::setFacadeApplication($app);
        $this->assertSame('baz', FacadeStub::bar());

        // Clear resolved instance and resolve the second time
        FacadeStub::clearResolvedInstance();
        $this->assertSame('baz', FacadeStub::bar());

        // Clear resolved instance through parent and resolve the third time
        Facade::clearResolvedInstance('foo');
        $this->assertSame('baz', FacadeStub::bar());

        $this->assertSame(3, $service->calls);
    }

    public function testFacadeResolvesAgainAfterClearingAll()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => $service = new FacadeTestService]);

        // Resolve for the first time
        FacadeStub::setFacadeApplication($app);
        $this->assertSame('baz', FacadeStub::bar());

        // Clear all resolved instances and resolve a second time
        Facade::clearResolvedInstances();
        $this->assertSame('baz', FacadeStub::bar());

        $this->assertSame(2, $service->calls);
    }
}

class FacadeStub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'foo';
    }
}

class FacadeStubExtendingAuth extends Auth
{
    //
}

class FacadeStubWithAlternative extends Facade
{
    protected static function testingAlternative()
    {
        return 'Use the real thing.';
    }

    protected static function getFacadeAccessor()
    {
        return 'foo';
    }
}

class FacadeStubForwardingTarget
{
    public function store()
    {
        return 'store';
    }

    public function __call($method, $parameters)
    {
        return $method;
    }
}

class FacadeStubMacroableTarget
{
    use Macroable;
}

class FacadeStubTarget
{
    public function foo($arg = null)
    {
        return 'real';
    }

    public function foo2($arg = null)
    {
        return 'real';
    }
}

class FacadeStubTargetWithReservedNames
{
    public function allows($ability)
    {
        return false;
    }
}

class ApplicationStub implements ArrayAccess
{
    protected $attributes = [];

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function instance($key, $instance)
    {
        $this->attributes[$key] = $instance;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($key): mixed
    {
        return $this->attributes[$key];
    }

    public function offsetSet($key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function offsetUnset($key): void
    {
        unset($this->attributes[$key]);
    }
}

class FacadeTestService
{
    public int $calls = 0;

    public function bar()
    {
        $this->calls++;

        return 'baz';
    }
}
