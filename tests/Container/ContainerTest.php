<?php

use Illuminate\Container\Container;

class ContainerContainerTest extends PHPUnit_Framework_TestCase {

	public function testClosureResolution()
	{
		$container = new Container;
		$container->bind('name', function() { return 'Taylor'; });
		$this->assertEquals('Taylor', $container->make('name'));
	}


	public function testBindIfDoesntRegisterIfServiceAlreadyRegistered()
	{
		$container = new Container;
		$container->bind('name', function() { return 'Taylor'; });
		$container->bindIf('name', function() { return 'Dayle'; });

		$this->assertEquals('Taylor', $container->make('name'));
	}


	public function testSharedClosureResolution()
	{
		$container = new Container;
		$class = new stdClass;
		$container->singleton('class', function() use ($class) { return $class; });
		$this->assertTrue($class === $container->make('class'));
	}


	public function testAutoConcreteResolution()
	{
		$container = new Container;
		$this->assertTrue($container->make('ContainerConcreteStub') instanceof ContainerConcreteStub);
	}


	public function testSharedConcreteResolution()
	{
		$container = new Container;
		$container->singleton('ContainerConcreteStub');
		$bindings = $container->getBindings();

		$var1 = $container->make('ContainerConcreteStub');
		$var2 = $container->make('ContainerConcreteStub');
		$this->assertTrue($var1 === $var2);
	}


	public function testAbstractToConcreteResolution()
	{
		$container = new Container;
		$container->bind('IContainerContractStub', 'ContainerImplementationStub');
		$class = $container->make('ContainerDependentStub');
		$this->assertTrue($class->impl instanceof ContainerImplementationStub);
	}


	public function testNestedDependencyResolution()
	{
		$container = new Container;
		$container->bind('IContainerContractStub', 'ContainerImplementationStub');
		$class = $container->make('ContainerNestedDependentStub');
		$this->assertTrue($class->inner instanceof ContainerDependentStub);
		$this->assertTrue($class->inner->impl instanceof ContainerImplementationStub);
	}


	public function testContainerIsPassedToResolvers()
	{
		$container = new Container;
		$container->bind('something', function($c) { return $c; });
		$c = $container->make('something');
		$this->assertTrue($c === $container);
	}


	public function testArrayAccess()
	{
		$container = new Container;
		$container['something'] = function() { return 'foo'; };
		$this->assertTrue(isset($container['something']));
		$this->assertEquals('foo', $container['something']);
		unset($container['something']);
		$this->assertFalse(isset($container['something']));
	}


	public function testAliases()
	{
		$container = new Container;
		$container['foo'] = 'bar';
		$container->alias('foo', 'baz');
		$this->assertEquals('bar', $container->make('foo'));
		$this->assertEquals('bar', $container->make('baz'));
		$container->bind(array('bam' => 'boom'), function() { return 'pow'; });
		$this->assertEquals('pow', $container->make('bam'));
		$this->assertEquals('pow', $container->make('boom'));
		$container->instance(array('zoom' => 'zing'), 'wow');
		$this->assertEquals('wow', $container->make('zoom'));
		$this->assertEquals('wow', $container->make('zing'));
	}


	public function testShareMethod()
	{
		$container = new Container;
		$closure = $container->share(function() { return new stdClass; });
		$class1 = $closure($container);
		$class2 = $closure($container);
		$this->assertTrue($class1 === $class2);
	}


	public function testBindingsCanBeOverridden()
	{
		$container = new Container;
		$container['foo'] = 'bar';
		$foo = $container['foo'];
		$container['foo'] = 'baz';
		$this->assertEquals('baz', $container['foo']);
	}


	public function testExtendedBindings()
	{
		$container = new Container;
		$container['foo'] = 'foo';
		$container->extend('foo', function($old, $container)
		{
			return $old.'bar';
		});

		$this->assertEquals('foobar', $container->make('foo'));

		$container = new Container;

		$container['foo'] = $container->share(function()
		{
			return (object) array('name' => 'taylor');
		});
		$container->extend('foo', function($old, $container)
		{
			$old->age = 26;
			return $old;
		});

		$result = $container->make('foo');

		$this->assertEquals('taylor', $result->name);
		$this->assertEquals(26, $result->age);
		$this->assertTrue($result === $container->make('foo'));
	}


	public function testMultipleExtends()
	{
		$container = new Container;
		$container['foo'] = 'foo';
		$container->extend('foo', function($old, $container)
		{
			return $old.'bar';
		});
		$container->extend('foo', function($old, $container)
		{
			return $old.'baz';
		});

		$this->assertEquals('foobarbaz', $container->make('foo'));
	}


	public function testParametersCanBePassedThroughToClosure()
	{
		$container = new Container;
		$container->bind('foo', function($c, $parameters)
		{
			return $parameters;
		});

		$this->assertEquals(array(1, 2, 3), $container->make('foo', array(1, 2, 3)));
	}


	public function testResolutionOfDefaultParameters()
	{
		$container = new Container;
		$instance = $container->make('ContainerDefaultValueStub');
		$this->assertInstanceOf('ContainerConcreteStub', $instance->stub);
		$this->assertEquals('taylor', $instance->default);
	}


	public function testResolvingCallbacksAreCalled()
	{
		$container = new Container;
		$container->resolving(function($object) { return $object->name = 'taylor'; });
		$container->bind('foo', function() { return new StdClass; });
		$instance = $container->make('foo');

		$this->assertEquals('taylor', $instance->name);
	}

}

class ContainerConcreteStub {}

interface IContainerContractStub {}

class ContainerImplementationStub implements IContainerContractStub {}

class ContainerDependentStub {
	public $impl;
	public function __construct(IContainerContractStub $impl)
	{
		$this->impl = $impl;
	}
}

class ContainerNestedDependentStub {
	public $inner;
	public function __construct(ContainerDependentStub $inner)
	{
		$this->inner = $inner;
	}
}

class ContainerDefaultValueStub {
	public $stub; public $default;
	public function __construct(ContainerConcreteStub $stub, $default = 'taylor')
	{
		$this->stub = $stub;
		$this->default = $default;
	}
}