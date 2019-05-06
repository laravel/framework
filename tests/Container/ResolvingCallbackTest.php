<?php

namespace Illuminate\Tests\Container;

use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class ResolvingCallbackTest extends TestCase
{
    public function testResolvingCallbacksAreCalledForSpecificAbstracts()
    {
        $container = new Container;
        $container->resolving('foo', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalled()
    {
        $container = new Container;
        $container->resolving(function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalledForType()
    {
        $container = new Container;
        $container->resolving(stdClass::class, function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksShouldBeFiredWhenCalledWithAliases()
    {
        $container = new Container;
        $container->alias(stdClass::class, 'std');
        $container->resolving('std', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalledOnceForImplementation()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testGlobalResolvingCallbacksAreCalledOnceForImplementation()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testResolvingCallbacksAreCalledOnceForSingletonConcretes()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);
        $container->bind(ResolvingImplementationStub::class);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(3, $callCounter);
    }

    public function testResolvingCallbacksCanStillBeAddedAfterTheFirstResolution()
    {
        $container = new Container;

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingImplementationStub::class);

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);
    }

    public function testResolvingCallbacksAreCanceledWhenInterfaceGetsBoundToSomeOtherConcrete()
    {
        $container = new Container;

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $callCounter = 0;
        $container->resolving(ResolvingImplementationStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStubTwo::class);
        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);
    }

    public function testResolvingCallbacksAreCalledOnceForStringAbstractions()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving('foo', function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind('foo', ResolvingImplementationStub::class);

        $container->make('foo');
        $this->assertEquals(1, $callCounter);

        $container->make('foo');
        $this->assertEquals(2, $callCounter);
    }

    public function testResolvingCallbacksForConcretesAreCalledOnceForStringAbstractions()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingImplementationStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind('foo', ResolvingImplementationStub::class);
        $container->bind('bar', ResolvingImplementationStub::class);
        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make('foo');
        $this->assertEquals(2, $callCounter);

        $container->make('bar');
        $this->assertEquals(3, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(4, $callCounter);
    }

    public function testResolvingCallbacksAreCalledOnceForImplementation2()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, function () {
            return new ResolvingImplementationStub;
        });

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(3, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(4, $callCounter);
    }

    public function testRebindingDoesNotAffectResolvingCallbacks()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);
        $container->bind(ResolvingContractStub::class, function () {
            return new ResolvingImplementationStub;
        });

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(3, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(4, $callCounter);
    }

    public function testParametersPassedIntoResolvingCallbacks()
    {
        $container = new Container;

        $container->resolving(ResolvingContractStub::class, function ($obj, $app) use ($container) {
            $this->assertInstanceOf(ResolvingContractStub::class, $obj);
            $this->assertInstanceOf(ResolvingImplementationStubTwo::class, $obj);
            $this->assertSame($container, $app);
        });

        $container->afterResolving(ResolvingContractStub::class, function ($obj, $app) use ($container) {
            $this->assertInstanceOf(ResolvingContractStub::class, $obj);
            $this->assertInstanceOf(ResolvingImplementationStubTwo::class, $obj);
            $this->assertSame($container, $app);
        });

        $container->afterResolving(function ($obj, $app) use ($container) {
            $this->assertInstanceOf(ResolvingContractStub::class, $obj);
            $this->assertInstanceOf(ResolvingImplementationStubTwo::class, $obj);
            $this->assertSame($container, $app);
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStubTwo::class);
        $container->make(ResolvingContractStub::class);
    }

    public function testResolvingCallbacksAreCallWhenRebindHappenForResolvedAbstract()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStubTwo::class);
        $this->assertEquals(2, $callCounter);

        $container->make(ResolvingImplementationStubTwo::class);
        $this->assertEquals(3, $callCounter);

        $container->bind(ResolvingContractStub::class, function () {
            return new ResolvingImplementationStubTwo();
        });
        $this->assertEquals(4, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(5, $callCounter);
    }

    public function testRebindingDoesNotAffectMultipleResolvingCallbacks()
    {
        $container = new Container;

        $callCounter = 0;

        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->resolving(ResolvingImplementationStubTwo::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        // it should call the callback for interface
        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        // it should call the callback for interface
        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);

        // should call the callback for the interface it implements
        // plus the callback for ResolvingImplementationStubTwo.
        $container->make(ResolvingImplementationStubTwo::class);
        $this->assertEquals(4, $callCounter);
    }

    public function testResolvingCallbacksAreCalledForInterfaces()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingContractStub::class);

        $this->assertEquals(1, $callCounter);
    }

    public function testResolvingCallbacksAreCalledForConcretesWhenAttachedOnInterface()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingImplementationStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testResolvingCallbacksAreCalledForConcretesWhenAttachedOnConcretes()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingImplementationStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testResolvingCallbacksAreCalledForConcretesWithNoBinding()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingImplementationStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);
        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testResolvingCallbacksAreCalledForInterFacesWithNoBinding()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testAfterResolvingCallbacksAreCalledOnceForImplementation()
    {
        $container = new Container;

        $callCounter = 0;
        $container->afterResolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(2, $callCounter);
    }
}

interface ResolvingContractStub
{
    //
}

class ResolvingImplementationStub implements ResolvingContractStub
{
    //
}

class ResolvingImplementationStubTwo implements ResolvingContractStub
{
    //
}
