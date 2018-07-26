<?php

use Illuminate\Container\Container;

class ContainerContainerTest extends TestCase {

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
		$this->assertSame($class, $container->make('class'));
	}


	public function testAutoConcreteResolution()
	{
		$container = new Container;
		$this->assertInstanceOf('ContainerConcreteStub', $container->make('ContainerConcreteStub'));
	}


	public function testSlashesAreHandled()
	{
		$container = new Container;
		$container->bind('\Foo', function() { return 'hello'; });
		$this->assertEquals('hello', $container->make('Foo'));
	}


	public function testParametersCanOverrideDependencies()
	{
		$container = new Container;
		$stub = new ContainerDependentStub($mock = $this->getMock('IContainerContractStub'));
		$resolved = $container->make('ContainerNestedDependentStub', array($stub));
		$this->assertInstanceOf('ContainerNestedDependentStub', $resolved);
		$this->assertEquals($mock, $resolved->inner->impl);
	}


	public function testSharedConcreteResolution()
	{
		$container = new Container;
		$container->singleton('ContainerConcreteStub');
		$bindings = $container->getBindings();

		$var1 = $container->make('ContainerConcreteStub');
		$var2 = $container->make('ContainerConcreteStub');
		$this->assertSame($var1, $var2);
	}


	public function testAbstractToConcreteResolution()
	{
		$container = new Container;
		$container->bind('IContainerContractStub', 'ContainerImplementationStub');
		$class = $container->make('ContainerDependentStub');
		$this->assertInstanceOf('ContainerImplementationStub', $class->impl);
	}


	public function testNestedDependencyResolution()
	{
		$container = new Container;
		$container->bind('IContainerContractStub', 'ContainerImplementationStub');
		$class = $container->make('ContainerNestedDependentStub');
		$this->assertInstanceOf('ContainerDependentStub', $class->inner);
		$this->assertInstanceOf('ContainerImplementationStub', $class->inner->impl);
	}


	public function testContainerIsPassedToResolvers()
	{
		$container = new Container;
		$container->bind('something', function($c) { return $c; });
		$c = $container->make('something');
		$this->assertSame($c, $container);
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
		$this->assertSame($class1, $class2);
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
		$this->assertSame($result, $container->make('foo'));
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


	public function testExtendInstancesArePreserved()
	{
		$container = new Container;
		$container->bind('foo', function() { $obj = new StdClass; $obj->foo = 'bar'; return $obj; });
		$obj = new StdClass; $obj->foo = 'foo';
		$container->instance('foo', $obj);
		$container->extend('foo', function($obj, $container) { $obj->bar = 'baz'; return $obj; });
		$container->extend('foo', function($obj, $container) { $obj->baz = 'foo'; return $obj; });
		$this->assertEquals('foo', $container->make('foo')->foo);
	}


	public function testExtendIsLazyInitialized()
	{
		$container = new Container;
		$container->bind('ContainerLazyExtendStub');
		$container->extend('ContainerLazyExtendStub', function($obj, $container) { $obj->init(); return $obj; });
		$this->assertFalse(ContainerLazyExtendStub::$initialized);
		$container->make('ContainerLazyExtendStub');
		$this->assertTrue(ContainerLazyExtendStub::$initialized);
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


	public function testResolvingCallbacksAreCalledForSpecificAbstracts()
	{
		$container = new Container;
		$container->resolving('foo', function($object) { return $object->name = 'taylor'; });
		$container->bind('foo', function() { return new StdClass; });
		$instance = $container->make('foo');

		$this->assertEquals('taylor', $instance->name);
	}


	public function testResolvingCallbacksAreCalled()
	{
		$container = new Container;
		$container->resolvingAny(function($object) { return $object->name = 'taylor'; });
		$container->bind('foo', function() { return new StdClass; });
		$instance = $container->make('foo');

		$this->assertEquals('taylor', $instance->name);
	}


	public function testUnsetRemoveBoundInstances()
	{
		$container = new Container;
		$container->instance('object', new StdClass);
		unset($container['object']);

		$this->assertFalse($container->bound('object'));
	}


	public function testReboundListeners()
	{
		unset($_SERVER['__test.rebind']);

		$container = new Container;
		$container->bind('foo', function() {});
		$container->rebinding('foo', function() { $_SERVER['__test.rebind'] = true; });
		$container->bind('foo', function() {});

		$this->assertTrue($_SERVER['__test.rebind']);
	}


	public function testReboundListenersOnInstances()
	{
		unset($_SERVER['__test.rebind']);

		$container = new Container;
		$container->instance('foo', function() {});
		$container->rebinding('foo', function() { $_SERVER['__test.rebind'] = true; });
		$container->instance('foo', function() {});

		$this->assertTrue($_SERVER['__test.rebind']);
	}


	public function testPassingSomePrimitiveParameters()
	{
		$container = new Container;
		$value = $container->make('ContainerMixedPrimitiveStub', array('first' => 'taylor', 'last' => 'otwell'));
		$this->assertInstanceOf('ContainerMixedPrimitiveStub', $value);
		$this->assertEquals('taylor', $value->first);
		$this->assertEquals('otwell', $value->last);
		$this->assertInstanceOf('ContainerConcreteStub', $value->stub);

		$container = new Container;
		$value = $container->make('ContainerMixedPrimitiveStub', array(0 => 'taylor', 2 => 'otwell'));
		$this->assertInstanceOf('ContainerMixedPrimitiveStub', $value);
		$this->assertEquals('taylor', $value->first);
		$this->assertEquals('otwell', $value->last);
		$this->assertInstanceOf('ContainerConcreteStub', $value->stub);
	}


	public function testCreatingBoundConcreteClassPassesParameters()
	{
		$container = new Container;
		$container->bind('TestAbstractClass', 'ContainerConstructorParameterLoggingStub');
		$parameters = array('First', 'Second');
		$instance = $container->make('TestAbstractClass', $parameters);
		$this->assertEquals($parameters, $instance->receivedParameters);
	}


	public function testInternalClassWithDefaultParameters()
	{
		$this->expectException('Illuminate\Container\BindingResolutionException', 'Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class ContainerMixedPrimitiveStub');
		$container = new Container;
		$parameters = array();
		$container->make('ContainerMixedPrimitiveStub', $parameters);
	}


	public function testUnsetAffectsResolved()
	{
		$container = new Container;
		$container->make('ContainerConcreteStub');

		unset($container['ContainerConcreteStub']);
		$this->assertFalse($container->resolved('ContainerConcreteStub'));
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

class ContainerMixedPrimitiveStub {
	public $first; public $last; public $stub;
	public function __construct($first, ContainerConcreteStub $stub, $last)
	{
		$this->stub = $stub;
		$this->last = $last;
		$this->first = $first;
	}
}

class ContainerConstructorParameterLoggingStub {
	public $receivedParameters;

	public function __construct($first, $second)
	{
		$this->receivedParameters = func_get_args();
	}
}

class ContainerLazyExtendStub {
	public static $initialized = false;
	public function init() { static::$initialized = true; }
}
