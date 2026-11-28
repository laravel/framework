<?php

namespace Illuminate\Tests\Support;

use ArrayAccess;
use Illuminate\Support\Facades\Facade;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Exceptions\StaticMethodException;
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
        $this->assertFalse(Facade::usesDouble());

        Facade::useDouble();
        $this->assertTrue(Facade::usesDouble());

        Facade::useMockery();
        $this->assertFalse(Facade::usesDouble());
    }

    public function testShouldHaveReceivedForwardsToMockerySpy()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        $spy = FacadeStub::spy();

        FacadeStub::foo('bar');

        $this->assertInstanceOf(MockInterface::class, $spy);
        FacadeStub::shouldHaveReceived('foo')->with('bar')->once();
        FacadeStub::shouldHaveReceived('foo', ['bar']);
        FacadeStub::shouldNotHaveReceived('foo2');
    }

    public function testShouldNotReceiveForwardsToMockeryMock()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::shouldReceive('foo');

        $this->assertInstanceOf(Mockery\ExpectationInterface::class, FacadeStub::shouldNotReceive('foo2'));

        FacadeStub::foo();
    }

    public function testShouldReceiveReturnsADouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::shouldReceive('foo')->with('bar')->returns('baz');

        $this->assertInstanceOf(DoubleInterface::class, $app['foo']);
        $this->assertInstanceOf(FacadeStubTarget::class, $app['foo']);
        $this->assertSame('baz', FacadeStub::foo('bar'));
    }

    public function testShouldReceiveCreatesAStrictDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::shouldReceive('foo');

        $this->expectException(PHPUnitUnexpectedCallException::class);

        FacadeStub::foo2();
    }

    public function testExpectsCreatesAStrictDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::expects('foo');

        $this->expectException(PHPUnitUnexpectedCallException::class);

        FacadeStub::foo2();
    }

    public function testSpyReturnsADouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        $this->assertInstanceOf(DoubleInterface::class, $spy = FacadeStub::spy());
        $this->assertSame($spy, $app['foo']);

        $this->assertNull(FacadeStub::foo());

        $spy->received('foo');
        FacadeStub::shouldHaveReceived('foo');
    }

    public function testShouldReceiveCanBeCalledTwiceWithDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::expects('foo')->with('bar')->returns('baz');
        FacadeStub::expects('foo2')->with('bar2')->returns('baz2');

        $this->assertSame('baz', $app['foo']->foo('bar'));
        $this->assertSame('baz2', $app['foo']->foo2('bar2'));
    }

    public function testDoubleRequiresUnderlyingInstance()
    {
        Facade::useDouble();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A facade root has not been set.');

        FacadeStub::expects('foo');
    }

    public function testShouldNotReceiveWithDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::shouldReceive('foo');
        FacadeStub::shouldNotReceive('foo2');

        FacadeStub::foo();

        $this->expectException(AssertionFailedError::class);

        FacadeStub::foo2();
    }

    public function testShouldHaveReceivedWithDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::spy();

        FacadeStub::foo('bar');

        FacadeStub::shouldHaveReceived('foo', ['bar']);
        FacadeStub::shouldHaveReceived('foo')->with('bar')->times(1);
        FacadeStub::shouldNotHaveReceived('foo', ['baz']);
        FacadeStub::shouldNotHaveReceived('foo2');

        $this->expectException(AssertionFailedError::class);

        FacadeStub::shouldHaveReceived('foo', ['baz']);
    }

    public function testShouldHaveReceivedWithSwappedDouble()
    {
        Facade::useDouble();

        FacadeStub::swap(Double::for(FacadeStubTarget::class));

        FacadeStub::foo('bar');

        FacadeStub::shouldHaveReceived('foo')->with('bar');
    }

    public function testPartialMockPassesThroughWithDouble()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        FacadeStub::partialMock()->allows('foo2')->returns('baz');

        $this->assertSame('real', FacadeStub::foo());
        $this->assertSame('baz', FacadeStub::foo2());
    }

    public function testRootWithReservedMethodNamesCanBeDoubled()
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

    public function testStaticMethodCannotBeDoubled()
    {
        Facade::useDouble();
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new FacadeStubTarget]);
        FacadeStub::setFacadeApplication($app);

        $this->expectException(StaticMethodException::class);

        FacadeStub::shouldReceive('staticFoo');
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

    public static function staticFoo()
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
