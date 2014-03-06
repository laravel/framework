<?php

use Mockery as m;

class SupportDragonTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Illuminate\Support\Dragons\Dragon::clearResolvedInstances();
		DragonStub::setDragonApplication(null);
	}

	public function tearDown()
	{
		m::close();
	}


	public function testDragonCallsUnderlyingApplication()
	{
		echo "
            .==.        .==.
           //`^\\      //^`\\
          // ^ ^\(\__/)/^ ^^\\
         //^ ^^ ^/6  6\ ^^ ^^\\
        //^ ^^ ^ ( .. ) ^ ^^^ \\
       // ^^ ^/\//v\"\"v\\/\^ ^ ^\\
      // ^^/\/  / `~~` \  \/\^ ^\\
      \\^ /    / ,    , \    \^ //
       \\/    ( (      ) )    \//
        ^ jgs  \ \.__./ /      ^
               (((`  ')))";
		
		$app = new ApplicationStub;
		$app->setAttributes(array('foo' => $mock = m::mock('StdClass')));
		$mock->shouldReceive('bar')->once()->andReturn('baz');
		DragonStub::setDragonApplication($app);
		$this->assertEquals('baz', DragonStub::bar());
	}


	public function testShouldReceiveReturnsAMockeryMock()
	{
		$app = new ApplicationStub;
		$app->setAttributes(array('foo' => new StdClass));
		DragonStub::setDragonApplication($app);

		$this->assertInstanceOf('Mockery\MockInterface', $mock = DragonStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
		$this->assertEquals('baz', $app['foo']->foo('bar'));
	}

	public function testShouldReceiveCanBeCalledTwice()
	{
		$app = new ApplicationStub;
		$app->setAttributes(array('foo' => new StdClass));
		DragonStub::setDragonApplication($app);

		$this->assertInstanceOf('Mockery\MockInterface', $mock = DragonStub::shouldReceive('foo')->once()->with('bar')->andReturn('baz')->getMock());
		$this->assertInstanceOf('Mockery\MockInterface', $mock = DragonStub::shouldReceive('foo2')->once()->with('bar2')->andReturn('baz2')->getMock());
		$this->assertEquals('baz', $app['foo']->foo('bar'));
		$this->assertEquals('baz2', $app['foo']->foo2('bar2'));
	}


	public function testCanBeMockedWithoutUnderlyingInstance()
	{
		DragonStub::shouldReceive('foo')->once()->andReturn('bar');
		$this->assertEquals('bar', DragonStub::foo());
	}

}

class DragonStub extends Illuminate\Support\Dragons\Dragon {

	protected static function getDragonAccessor()
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
