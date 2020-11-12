<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerExtendTest extends TestCase
{
    public function testExtendedBindings()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });

        $this->assertSame('foobar', $container->make('foo'));

        $container = new Container;

        $container->singleton('foo', function () {
            return (object) ['name' => 'taylor'];
        });
        $container->extend('foo', function ($old, $container) {
            $old->age = 26;

            return $old;
        });

        $result = $container->make('foo');

        $this->assertSame('taylor', $result->name);
        $this->assertEquals(26, $result->age);
        $this->assertSame($result, $container->make('foo'));
    }

    public function testExtendInstancesArePreserved()
    {
        $container = new Container;
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });

        $obj = new stdClass;
        $obj->foo = 'foo';
        $container->instance('foo', $obj);
        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });
        $container->extend('foo', function ($obj, $container) {
            $obj->baz = 'foo';

            return $obj;
        });

        $this->assertSame('foo', $container->make('foo')->foo);
        $this->assertSame('baz', $container->make('foo')->bar);
        $this->assertSame('foo', $container->make('foo')->baz);
    }

    public function testExtendIsLazyInitialized()
    {
        ContainerLazyExtendStub::$initialized = false;

        $container = new Container;
        $container->bind(ContainerLazyExtendStub::class);
        $container->extend(ContainerLazyExtendStub::class, function ($obj, $container) {
            $obj->init();

            return $obj;
        });
        $this->assertFalse(ContainerLazyExtendStub::$initialized);
        $container->make(ContainerLazyExtendStub::class);
        $this->assertTrue(ContainerLazyExtendStub::$initialized);
    }

    public function testExtendCanBeCalledBeforeBind()
    {
        $container = new Container;
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        $container['foo'] = 'foo';

        $this->assertSame('foobar', $container->make('foo'));
    }

    public function testExtendInstanceRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });

        $obj = new stdClass;
        $container->instance('foo', $obj);

        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        $this->assertTrue($_SERVER['_test_rebind']);
    }

    public function testExtendBindRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });
        $container->bind('foo', function () {
            return new stdClass;
        });

        $this->assertFalse($_SERVER['_test_rebind']);

        $container->make('foo');

        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        $this->assertTrue($_SERVER['_test_rebind']);
    }

    public function testExtensionWorksOnAliasedBindings()
    {
        $container = new Container;
        $container->singleton('something', function () {
            return 'some value';
        });
        $container->alias('something', 'something-alias');
        $container->extend('something-alias', function ($value) {
            return $value.' extended';
        });

        $this->assertSame('some value extended', $container->make('something'));
    }

    public function testMultipleExtends()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        $container->extend('foo', function ($old, $container) {
            return $old.'baz';
        });

        $this->assertSame('foobarbaz', $container->make('foo'));
    }

    public function testUnsetExtend()
    {
        $container = new Container;
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });

        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });

        unset($container['foo']);
        $container->forgetExtenders('foo');

        $container->bind('foo', function () {
            return 'foo';
        });

        $this->assertSame('foo', $container->make('foo'));
    }

    public function testGloballyExtendedBindings()
    {
        // Given a simple "foo" binding.
        $container = new Container;
        $container['foo'] = 'foo';

        // When we append "bar" to all bindings.
        $container->extend(function ($old, $container) {
            return $old . 'bar';
        });

        // Then we resolve "foobar".
        $this->assertSame('foobar', $container->make('foo'));
    }

    public function testGloballyExtendedSingletons()
    {
        // Given an registered "foo" singleton.
        $container = new Container;
        $container->singleton('foo', function () {
            return (object) ['name' => 'taylor'];
        });

        // When we add the age property to all bindings.
        $container->extend(function ($old, $container) {
            $old->age = 26;

            return $old;
        });

        // Then the "foo" singleton has the "age" property.
        $result = $container->make('foo');
        $this->assertSame('taylor', $result->name);
        $this->assertEquals(26, $result->age);

        // And it stays the same instance no matter how many times we resolve it.
        $this->assertSame($result, $container->make('foo'));
    }

    public function testGloballyExtendedInstancesArePreserved()
    {
        // Given a "foo" simple binding.
        $container = new Container;
        $container->bind('foo', function () {
            return (object) ['foo' => 'bind'];
        });

        // And a "foo" instance.
        $obj = (object) ['foo' => 'instance'];
        $container->instance('foo', $obj);

        // When we extend all bindings twice.
        $container->extend(function ($obj, $container) {
            $obj->bar = 'extended_once';

            return $obj;
        });
        $container->extend(function ($obj, $container) {
            $obj->baz = 'extended_twice';

            return $obj;
        });

        // Then the "foo" instance has been extended twice.
        $this->assertSame('instance', $container->make('foo')->foo);
        $this->assertSame('extended_once', $container->make('foo')->bar);
        $this->assertSame('extended_twice', $container->make('foo')->baz);
    }
}

class ContainerLazyExtendStub
{
    public static $initialized = false;

    public function init()
    {
        static::$initialized = true;
    }
}
