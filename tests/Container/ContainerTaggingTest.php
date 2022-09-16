<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ContainerTaggingTest extends TestCase
{
    public function testContainerTags()
    {
        $container = new Container;
        $container->tag(ContainerImplementationTaggedStub::class, 'foo', 'bar');
        $container->tag(ContainerImplementationTaggedStubTwo::class, ['foo']);

        $this->assertCount(1, $container->tagged('bar'));
        $this->assertCount(2, $container->tagged('foo'));

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
        }

        $barResults = [];
        foreach ($container->tagged('bar') as $bar) {
            $barResults[] = $bar;
        }

        $this->assertInstanceOf(ContainerImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationTaggedStub::class, $barResults[0]);
        $this->assertInstanceOf(ContainerImplementationTaggedStubTwo::class, $fooResults[1]);

        $container = new Container;
        $container->tag([ContainerImplementationTaggedStub::class, ContainerImplementationTaggedStubTwo::class], ['foo']);
        $this->assertCount(2, $container->tagged('foo'));

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ContainerImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationTaggedStubTwo::class, $fooResults[1]);

        $this->assertCount(0, $container->tagged('this_tag_does_not_exist'));
    }

    public function testTaggedServicesAreLazyLoaded()
    {
        $container = $this->createPartialMock(Container::class, ['make']);
        $container->expects($this->once())->method('make')->willReturn(new ContainerImplementationTaggedStub);

        $container->tag(ContainerImplementationTaggedStub::class, ['foo']);
        $container->tag(ContainerImplementationTaggedStubTwo::class, ['foo']);

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
            break;
        }

        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf(ContainerImplementationTaggedStub::class, $fooResults[0]);
    }

    public function testLazyLoadedTaggedServicesCanBeLoopedOverMultipleTimes()
    {
        $container = new Container;
        $container->tag(ContainerImplementationTaggedStub::class, 'foo');
        $container->tag(ContainerImplementationTaggedStubTwo::class, ['foo']);

        $services = $container->tagged('foo');

        $fooResults = [];
        foreach ($services as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ContainerImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationTaggedStubTwo::class, $fooResults[1]);

        $fooResults = [];
        foreach ($services as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ContainerImplementationTaggedStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationTaggedStubTwo::class, $fooResults[1]);
    }

    public function testItHasAClassmapProperty()
    {
        $container = new Container();

        // The stubs in this file are not loaded by the composer classmap file.
        // Insert them manually to assert they are available for testing
        $classmapProperty = new ReflectionProperty(get_class($container), 'classmap');
        $classmapProperty->setAccessible(true);
        $classmapProperty->setValue($container, get_declared_classes());

        $this->assertGreaterThanOrEqual(1, $classmapProperty->getValue($container));

        return $container;
    }

    /**
     * @depends testItHasAClassmapProperty
     */
    public function testClassesCanBeTaggedByTheirInterface(Container $container)
    {
        $container->tagInstanceof(IContainerTaggedContractStub::class, 'foo-instanceof-interface');
        $this->assertCount(3, $container->tagged('foo-instanceof-interface'));

        $container->tagInstanceof(IContainerTaggedContractStub::class, ['foo-instanceof-interface-2', 'bar-instanceof-interface-2']);
        $this->assertCount(3, $container->tagged('foo-instanceof-interface-2'));
        $this->assertCount(3, $container->tagged('bar-instanceof-interface-2'));

        $container->tagInstanceof(
            [
                IContainerTaggedContractStub::class,
                IContainerTaggedContractStubTwo::class,
            ],
            'foo-instanceof-multiple-interface'
        );
        $this->assertCount(4, $container->tagged('foo-instanceof-multiple-interface'));
    }

    /**
     * @depends testItHasAClassmapProperty
     */
    public function testClassesCanBeTaggedByTheirParentClass(Container $container)
    {
        $container->tagInstanceof(ContainerWithoutImplementationTaggedStub::class, 'foo-instanceof-class');
        $this->assertCount(2, $container->tagged('foo-instanceof-class'));

        $container->tagInstanceof(
            [
                ContainerImplementationTaggedStub::class,
                ContainerWithoutImplementationTaggedStub::class,
            ],
            'foo-instanceof-multiple-classes'
        );
        $this->assertCount(4, $container->tagged('foo-instanceof-multiple-classes'));
    }

    /**
     * @depends testItHasAClassmapProperty
     */
    public function testClassesCanBeTaggedByEitherTheirParentClassOrInterfaces(Container $container)
    {
        $container->tagInstanceof(
            [
                IContainerTaggedContractStub::class,
                ContainerWithoutImplementationTaggedStub::class,
            ],
            'foo-instanceof-combined-classes-and-interfaces'
        );
        $this->assertCount(5, $container->tagged('foo-instanceof-combined-classes-and-interfaces'));
    }
}

interface IContainerTaggedContractStub
{
    //
}

interface IContainerTaggedContractStubTwo
{
    //
}

class ContainerImplementationTaggedStub implements IContainerTaggedContractStub
{
    //
}

class ContainerImplementationTaggedStubTwo implements IContainerTaggedContractStub
{
    //
}

class ContainerExtendingContainerWithImplementationTaggedStub extends ContainerImplementationTaggedStub
{
    //
}

class ContainerWithoutImplementationTaggedStub
{
    //
}

class ContainerWithoutImplementationTaggedStubTwo extends ContainerWithoutImplementationTaggedStub
{
    //
}

class ContainerImplementationTaggedStubThree implements IContainerTaggedContractStubTwo
{
    //
}
