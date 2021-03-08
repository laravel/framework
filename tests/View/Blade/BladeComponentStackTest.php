<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\ViewFinderInterface;
use Mockery;
use Mockery as m;

class BladeComponentStackTest extends AbstractBladeTestCase
{
    protected $factory;

    protected function tearDown(): void
    {
        Mockery::close();
    }


    public function testComponentStackOrder()
    {
        $factory = $this->getFactory();
        $this->mockBasicComponent();

        $this->assertCount(0, $factory->getComponentStack());

        $parent = new TestParentComponent();
        $factory->startComponent($parent, $parent->data());

        $this->assertCount(1, $factory->getComponentStack());
        $this->assertEquals($parent, $factory->getComponentStack()[0]);

        $intermediate = new TestIntermediateComponent();
        $factory->startComponent($intermediate, $intermediate->data());

        $this->assertCount(2, $factory->getComponentStack());
        $this->assertEquals($parent, $factory->getComponentStack()[0]);
        $this->assertEquals($intermediate, $factory->getComponentStack()[1]);

        $child = new TestChildComponent();
        $factory->startComponent($child, $child->data());

        $this->assertEquals($parent, $child->getParent());
        
        $this->assertCount(3, $factory->getComponentStack());
        $this->assertEquals($parent, $factory->getComponentStack()[0]);
        $this->assertEquals($intermediate, $factory->getComponentStack()[1]);
        $this->assertEquals($child, $factory->getComponentStack()[2]);

        $factory->renderComponent();

        $this->assertCount(2, $factory->getComponentStack());
        $this->assertEquals($parent, $factory->getComponentStack()[0]);
        $this->assertEquals($intermediate, $factory->getComponentStack()[1]);

        $factory->renderComponent();

        $this->assertCount(1, $factory->getComponentStack());
        $this->assertEquals($parent, $factory->getComponentStack()[0]);

        $factory->renderComponent();

        $this->assertCount(0, $factory->getComponentStack());
    }


    protected function getFactory()
    {
        if (!$this->factory) {
            $this->factory = new Factory(
                m::mock(EngineResolver::class),
                m::mock(ViewFinderInterface::class),
                m::mock(DispatcherContract::class)
            );

            Container::getInstance()->instance('view', $this->factory);
        }

        return $this->factory;
    }

    protected function mockBasicComponent()
    {
        $factory = $this->getFactory();

        $factory->getFinder()->shouldReceive('find')->andReturn(__DIR__.'/../fixtures/basic.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->shouldReceive('dispatch');
    }
}

class TestParentComponent extends Component
{

    public function render()
    {
        return 'parent';
    }
}

class TestIntermediateComponent extends Component
{

    public function render()
    {
        return 'intermediate';
    }
}

class TestChildComponent extends Component
{

    public function getParent()
    {
        return Collection::make(Container::getInstance()->make('view')->getComponentStack())
            ->reverse()
            ->first(function($component) {
                return $component instanceof TestParentComponent;
            });
    }

    public function render()
    {
        return 'child';
    }
}
