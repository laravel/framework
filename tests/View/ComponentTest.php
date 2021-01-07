<?php

namespace Illuminate\Tests\View;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Illuminate\View\Factory;
use Illuminate\View\View;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    protected $viewFactory;
    protected $config;

    protected function setUp(): void
    {
        $this->config = m::mock(Config::class);

        $container = new Container;

        $this->viewFactory = m::mock(Factory::class);

        $container->instance('view', $this->viewFactory);
        $container->alias('view', FactoryContract::class);
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
        $this->assertSame('__components::c6327913fef3fca4518bcd7df1d0ff630758e241', $component->resolveView());
    }

    public function testRegularViewsGetReturned()
    {
        $view = m::mock(View::class);
        $this->viewFactory->shouldReceive('make')->once()->with('alert', [], [])->andReturn($view);

        $component = new TestRegularViewComponent();

        $this->assertSame($view, $component->resolveView());
    }

    public function testRegularViewNamesGetReturned()
    {
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(true);
        $this->viewFactory->shouldReceive('addNamespace')->never();

        $component = new TestRegularViewNameViewComponent();

        $this->assertSame('alert', $component->resolveView());
    }

    public function testHtmlablesGetReturned()
    {
        $component = new TestHtmlableReturningViewComponent();

        $view = $component->resolveView();

        $this->assertInstanceOf(Htmlable::class, $view);
        $this->assertSame('<p>Hello foo</p>', $view->toHtml());
    }
}

class TestInlineViewComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function render()
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

    public function render()
    {
        return view('alert');
    }
}

class TestRegularViewNameViewComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function render()
    {
        return 'alert';
    }
}

class TestHtmlableReturningViewComponent extends Component
{
    protected $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function render()
    {
        return new HtmlString("<p>Hello {$this->title}</p>");
    }
}
