<?php

namespace Illuminate\Tests\View;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    protected $viewFactory;
    protected $config;

    public function setUp(): void
    {
        $this->config = m::mock(Config::class);

        $container = new Container;

        $this->viewFactory = m::mock(Factory::class);

        $container->instance('view', $this->viewFactory);
        $container->instance('config', $this->config);

        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        m::close();

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
    }

    public function testInlineViewsGetCreated()
    {
        $this->config->shouldReceive('get')->once()->with('view.compiled')->andReturn('/tmp');
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(false);
        $this->viewFactory->shouldReceive('addNamespace')->once()->with('__components', '/tmp');

        $component = new TestInlineViewComponent();
        $this->assertSame('__components::c6327913fef3fca4518bcd7df1d0ff630758e241', $component->viewFile());
    }

    public function testRegularViewsGetReturned()
    {
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(true);
        $this->viewFactory->shouldReceive('addNamespace')->never();

        $component = new TestRegularViewComponent();

        $this->assertSame('alert', $component->viewFile());
    }
}

class TestInlineViewComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function view()
    {
        return 'Hello {{ $title }}';
    }
}

class TestRegularViewComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function view()
    {
        return 'alert';
    }
}
