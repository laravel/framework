<?php

namespace Illuminate\Tests\Support;

use stdClass;
use ArrayAccess;
use Mockery as m;
use ArgumentCountError;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Facade;

class SupportFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
        FacadeStub::setFacadeApplication(null);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testFacadeCallsUnderlyingApplication()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => $mock = m::mock(stdClass::class)]);
        $mock->shouldReceive('bar')->once()->andReturn('baz');
        FacadeStub::setFacadeApplication($app);
        $this->assertEquals('baz', FacadeStub::bar());
    }

    public function testShouldReceiveReturnsAMockeryMock()
    {
        $app = new ApplicationStub;
        $app->setAttributes(['foo' => new stdClass]);
        FacadeStub::setFacadeApplication($app);

        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
        $this->assertEquals('baz', $app['foo']->foo('bar'));
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

        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
        $this->assertInstanceOf(MockInterface::class, $mock = FacadeStub::shouldReceive('foo2')->once()->with('bar2')->andReturn('baz2')->getMock());
        $this->assertEquals('baz', $app['foo']->foo('bar'));
        $this->assertEquals('baz2', $app['foo']->foo2('bar2'));
    }

    public function testCanBeMockedWithoutUnderlyingInstance()
    {
        FacadeStub::shouldReceive('foo')->once()->andReturn('bar');
        $this->assertEquals('bar', FacadeStub::foo());
    }

    public function testDoesNotSwallowInternalTypeErrorOfTheTargetClass()
    {
        $app = new ApplicationStub;
        $app->setAttributes([
            'foo' => new ConcreteFacadeStub,
            FacadeStub1::class => new FacadeStub1(),
        ]);

        FacadeStub::setFacadeApplication($app);

        try {
            FacadeStub::faulty();
        } catch (ArgumentCountError $error) {
            $this->assertRegExp('/Too few arguments to function .*?FacadeStub1::method\(\), 0 passed in .* and exactly 1 expected/', $error->getMessage());
        }
    }

    public function testItCanInjectForFirstParam()
    {
        $app = new ApplicationStub;
        $app->setAttributes([
            'foo' => new ConcreteFacadeStub,
            FacadeStub1::class => new FacadeStub1(),
            FacadeStub2::class => new FacadeStub2(),
        ]);

        FacadeStub::setFacadeApplication($app);

        $this->assertEquals('def1'.'ab'.'def3', FacadeStub::m3(new FacadeStub1(), 'ab'));
        $this->assertEquals('def1'.'bb'.'def3', FacadeStub::m3('bb'));
        $this->assertEquals('def1'.'bb'.'cc', FacadeStub::m3('bb', 'cc'));
        $this->assertEquals('def1'.'bb'.'cc', FacadeStub::m3('bb', 'cc', 'dd'));
        $this->assertEquals('val1'.'def2'.'def3', FacadeStub::m3(new FacadeStub1('val1')));
        $this->assertEquals('def1'.'def2'.'def3', FacadeStub::m3());
    }

    public function testItCanInjectForSecondParam()
    {
        $app = new ApplicationStub;
        $app->setAttributes([
            'foo' => new ConcreteFacadeStub,
            FacadeStub1::class => new FacadeStub1(),
        ]);

        FacadeStub::setFacadeApplication($app);

        $this->assertEquals('abc'.FacadeStub1::class.'def3', FacadeStub::m5('abc'));
        $this->assertEquals('abc'.FacadeStub1::class.'def3', FacadeStub::m5('abc', new FacadeStub1()));
        $this->assertEquals('bb'.FacadeStub1::class.'cc', FacadeStub::m5('bb', 'cc'));
        $this->assertEquals('bb'.FacadeStub1::class.'cc', FacadeStub::m5('bb', new FacadeStub1, 'cc'));
    }

    public function testItCanInjectTwoDependencies()
    {
        $app = new ApplicationStub;
        $app->setAttributes([
            'foo' => new ConcreteFacadeStub,
            FacadeStub1::class => new FacadeStub1(),
            FacadeStub2::class => new FacadeStub2(),
        ]);

        FacadeStub::setFacadeApplication($app);

        $this->assertEquals('val1'.'def2'.'x_default', FacadeStub::m6(new FacadeStub1('val1'), 'x_'));
        $this->assertEquals('def1'.'val2'.'x_default', FacadeStub::m6(new FacadeStub2('val2'), 'x_'));
        $this->assertEquals('val1'.'val2'.'x_default', FacadeStub::m6(new FacadeStub1('val1'), new FacadeStub2('val2'), 'x_'));
        $this->assertEquals('val1'.'def2'.'x_y', FacadeStub::m6(new FacadeStub1('val1'), 'x_', 'y'));
        $this->assertEquals('def1'.'val2'.'x_y', FacadeStub::m6(new FacadeStub2('val2'), 'x_', 'y'));
        $this->assertEquals('def1'.'def2'.'x_default', FacadeStub::m6('x_'));
        $this->assertEquals('def1'.'def2'.'x_y', FacadeStub::m6('x_', 'y'));
    }
}

class FacadeStub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'foo';
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

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($key)
    {
        return $this->attributes[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function offsetUnset($key)
    {
        unset($this->attributes[$key]);
    }
}

class FacadeStub1
{
    public $a;

    public function __construct($a = 'def1')
    {
        $this->a = $a;
    }

    public function method($param)
    {
    }
}

class FacadeStub2
{
    public $b;

    public function __construct($b = 'def2')
    {
        $this->b = $b;
    }
}

class ConcreteFacadeStub
{
    public function m3(FacadeStub1 $p1, $p2 = 'def2', $p3 = 'def3')
    {
        return ($p1->a).$p2.$p3;
    }

    public function m4($p1, $p2 = 'def2')
    {
        return get_class($p1).$p2;
    }

    public function m5($p1, FacadeStub1 $p2, $p3 = 'def3')
    {
        return $p1.get_class($p2).$p3;
    }

    public function m6(FacadeStub1 $p1, FacadeStub2 $p2, $p3, $p4 = 'default')
    {
        return ($p1->a).($p2->b).$p3.$p4;
    }

    public function faulty(FacadeStub1 $p1)
    {
        $p1->method();
    }
}
