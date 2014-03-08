<?php

use Mockery as m;

class SupportSurrogateTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Illuminate\Support\Surrogates\Surrogate::clearResolvedInstances();
		SurrogateStub::setFacadeApplication(null);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testSurrogateCallsUnderlyingApplication()
	{
		$app = new ApplicationStub;
		$app->setAttributes(array('foo' => $mock = m::mock('StdClass')));
		$mock->shouldReceive('bar')->once()->andReturn('baz');
		SurrogateStub::setFacadeApplication($app);
		$this->assertEquals('baz', SurrogateStub::bar());
	}


	public function testShouldReceiveReturnsAMockeryMock()
	{
		$app = new ApplicationStub;
		$app->setAttributes(array('foo' => new StdClass));
		SurrogateStub::setFacadeApplication($app);

		$this->assertInstanceOf('Mockery\MockInterface', $mock = SurrogateStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
		$this->assertEquals('baz', $app['foo']->foo('bar'));
	}

	public function testShouldReceiveCanBeCalledTwice()
	{
		$app = new ApplicationStub;
		$app->setAttributes(array('foo' => new StdClass));
		SurrogateStub::setFacadeApplication($app);

		$this->assertInstanceOf('Mockery\MockInterface', $mock = SurrogateStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
		$this->assertInstanceOf('Mockery\MockInterface', $mock = SurrogateStub::shouldReceive('foo2')->once()->with('bar2')->andReturn('baz2')->getMock());
		$this->assertEquals('baz', $app['foo']->foo('bar'));
		$this->assertEquals('baz2', $app['foo']->foo2('bar2'));
	}


	public function testCanBeMockedWithoutUnderlyingInstance()
	{
		SurrogateStub::shouldReceive('foo')->once()->andReturn('bar');
		$this->assertEquals('bar', SurrogateStub::foo());
	}

}

class SurrogateStub extends Illuminate\Support\Surrogates\Surrogate {

	protected static function getFacadeAccessor()
	{
		return 'foo';
	}

}

class ApplicationStub implements ArrayAccess {

	protected $attributes = array();

	public function setAttributes($attributes) { $this->attributes = $attributes; }
	public function instance($key, $instance) { $this->attributes[$key] = $instance; }
	public function offsetExists($offset) { return isset($this->attributes[$offset]); }
	public function offsetGet($key) { return $this->attributes[$key]; }
	public function offsetSet($key, $value) { $this->attributes[$key] = $value; }
	public function offsetUnset($key) { unset($this->attributes[$key]); }

}
