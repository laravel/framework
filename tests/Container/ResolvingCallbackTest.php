<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

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

        $this->assertSame('taylor', $instance->name);
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

        $this->assertSame('taylor', $instance->name);
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

        $this->assertSame('taylor', $instance->name);
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

        $this->assertSame('taylor', $instance->name);
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

    public function testResolvingCallbacksAreCallWhenRebindHappens()
    {
        $container = new Container;

        $resolvingCallCounter = 0;
        $container->resolving(ResolvingContractStub::class, function () use (&$resolvingCallCounter) {
            $resolvingCallCounter++;
        });

        $rebindCallCounter = 0;
        $container->rebinding(ResolvingContractStub::class, function () use (&$rebindCallCounter) {
            $rebindCallCounter++;
        });

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(1, $resolvingCallCounter);
        $this->assertEquals(0, $rebindCallCounter);

        $container->bind(ResolvingContractStub::class, ResolvingImplementationStubTwo::class);
        $this->assertEquals(2, $resolvingCallCounter);
        $this->assertEquals(1, $rebindCallCounter);

        $container->make(ResolvingImplementationStubTwo::class);
        $this->assertEquals(3, $resolvingCallCounter);
        $this->assertEquals(1, $rebindCallCounter);

        $container->bind(ResolvingContractStub::class, fn () => new ResolvingImplementationStubTwo);
        $this->assertEquals(4, $resolvingCallCounter);
        $this->assertEquals(2, $rebindCallCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(5, $resolvingCallCounter);
        $this->assertEquals(2, $rebindCallCounter);
    }

    public function testResolvingCallbacksArentCalledWhenNoRebindingsAreRegistered()
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
        $this->assertEquals(1, $callCounter);

        $container->make(ResolvingImplementationStubTwo::class);
        $this->assertEquals(2, $callCounter);

        $container->bind(ResolvingContractStub::class, fn () => new ResolvingImplementationStubTwo);
        $this->assertEquals(2, $callCounter);

        $container->make(ResolvingContractStub::class);
        $this->assertEquals(3, $callCounter);
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

    public function testBeforeResolvingCallbacksAreCalled()
    {
        // Given a call counter initialized to zero.
        $container = new Container;
        $callCounter = 0;

        // And a contract/implementation stub binding.
        $container->bind(ResolvingContractStub::class, ResolvingImplementationStub::class);

        // When we add a before resolving callback that increment the counter by one.
        $container->beforeResolving(ResolvingContractStub::class, function () use (&$callCounter) {
            $callCounter++;
        });

        // Then resolving the implementation stub increases the counter by one.
        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);

        // And resolving the contract stub increases the counter by one.
        $container->make(ResolvingContractStub::class);
        $this->assertEquals(2, $callCounter);
    }

    public function testGlobalBeforeResolvingCallbacksAreCalled()
    {
        // Given a call counter initialized to zero.
        $container = new Container;
        $callCounter = 0;

        // When we add a global before resolving callback that increment that counter by one.
        $container->beforeResolving(function () use (&$callCounter) {
            $callCounter++;
        });

        // Then resolving anything increases the counter by one.
        $container->make(ResolvingImplementationStub::class);
        $this->assertEquals(1, $callCounter);
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
