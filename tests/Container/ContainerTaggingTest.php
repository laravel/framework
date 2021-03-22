<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

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
}

interface IContainerTaggedContractStub
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
