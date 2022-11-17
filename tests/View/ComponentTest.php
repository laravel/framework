<?php

namespace Illuminate\Tests\View;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
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
        parent::setUp();

        $this->config = m::mock(Config::class);

        $container = new Container;

        $this->viewFactory = m::mock(Factory::class);

        $container->instance('view', $this->viewFactory);
        $container->alias('view', FactoryContract::class);
        $container->instance('config', $this->config);

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        m::close();

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        Component::flushCache();
        Component::forgetFactory();

        parent::tearDown();
    }

    public function testInlineViewsGetCreated()
    {
        $this->config->shouldReceive('get')->once()->with('view.compiled')->andReturn('/tmp');
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(false);
        $this->viewFactory->shouldReceive('addNamespace')->once()->with('__components', '/tmp');

        $component = new TestInlineViewComponent;
        $this->assertSame('__components::c6327913fef3fca4518bcd7df1d0ff630758e241', $component->resolveView());
    }

    public function testRegularViewsGetReturnedUsingViewHelper()
    {
        $view = m::mock(View::class);
        $this->viewFactory->shouldReceive('make')->once()->with('alert', [], [])->andReturn($view);

        $component = new TestRegularViewComponentUsingViewHelper;

        $this->assertSame($view, $component->resolveView());
    }

    public function testRegularViewsGetReturnedUsingViewMethod()
    {
        $view = m::mock(View::class);
        $this->viewFactory->shouldReceive('make')->once()->with('alert', [], [])->andReturn($view);

        $component = new TestRegularViewComponentUsingViewMethod;

        $this->assertSame($view, $component->resolveView());
    }

    public function testRegularViewNamesGetReturned()
    {
        $this->viewFactory->shouldReceive('exists')->once()->andReturn(true);
        $this->viewFactory->shouldReceive('addNamespace')->never();

        $component = new TestRegularViewNameViewComponent;

        $this->assertSame('alert', $component->resolveView());
    }

    public function testHtmlablesGetReturned()
    {
        $component = new TestHtmlableReturningViewComponent;

        $view = $component->resolveView();

        $this->assertInstanceOf(Htmlable::class, $view);
        $this->assertSame('<p>Hello foo</p>', $view->toHtml());
    }

    public function testResolveWithUnresolvableDependency()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unresolvable dependency resolving');

        TestInlineViewComponentWhereRenderDependsOnProps::resolve([]);
    }

    public function testResolveDependenciesWithoutContainer()
    {
        $component = TestInlineViewComponentWhereRenderDependsOnProps::resolve(['content' => 'foo']);
        $this->assertSame('foo', $component->render());

        $component = new class extends Component
        {
            public $content;

            public function __construct($a = null, $b = null)
            {
                $this->content = $a.$b;
            }

            public function render()
            {
                return $this->content;
            }
        };

        $component = $component::resolve(['a' => 'a', 'b' => 'b']);
        $component = $component::resolve(['b' => 'b', 'a' => 'a']);
        $this->assertSame('ab', $component->render());
    }

    public function testResolveDependenciesWithContainerIfNecessary()
    {
        $component = TestInlineViewComponentWithContainerDependencies::resolve([]);
        $this->assertSame($this->viewFactory, $component->dependency);

        $component = TestInlineViewComponentWithContainerDependenciesAndProps::resolve(['content' => 'foo']);
        $this->assertSame($this->viewFactory, $component->dependency);
        $this->assertSame('foo', $component->render());
    }

    public function testResolveComponentsUsing()
    {
        $component = new TestInlineViewComponent;

        Component::resolveComponentsUsing(fn () => $component);

        $this->assertSame($component, Component::resolve('bar'));
    }

    public function testBladeViewCacheWithRegularViewNameViewComponent()
    {
        $component = new TestRegularViewNameViewComponent;

        $this->viewFactory->shouldReceive('exists')->twice()->andReturn(true);

        $this->assertSame('alert', $component->resolveView());
        $this->assertSame('alert', $component->resolveView());
        $this->assertSame('alert', $component->resolveView());
        $this->assertSame('alert', $component->resolveView());

        $cache = (fn () => $component::$bladeViewCache)->call($component);
        $this->assertSame([$component::class.'::alert' => 'alert'], $cache);

        $component::flushCache();

        $cache = (fn () => $component::$bladeViewCache)->call($component);
        $this->assertSame([], $cache);

        $this->assertSame('alert', $component->resolveView());
        $this->assertSame('alert', $component->resolveView());
        $this->assertSame('alert', $component->resolveView());
        $this->assertSame('alert', $component->resolveView());
    }

    public function testBladeViewCacheWithInlineViewComponent()
    {
        $component = new TestInlineViewComponent;

        $this->viewFactory->shouldReceive('exists')->twice()->andReturn(false);

        $this->config->shouldReceive('get')->twice()->with('view.compiled')->andReturn('/tmp');

        $this->viewFactory->shouldReceive('addNamespace')
            ->with('__components', '/tmp')
            ->twice();

        $compiledViewName = '__components::c6327913fef3fca4518bcd7df1d0ff630758e241';
        $contents = '::Hello {{ $title }}';
        $cacheKey = $component::class.$contents;

        $this->assertSame($compiledViewName, $component->resolveView());
        $this->assertSame($compiledViewName, $component->resolveView());
        $this->assertSame($compiledViewName, $component->resolveView());
        $this->assertSame($compiledViewName, $component->resolveView());

        $cache = (fn () => $component::$bladeViewCache)->call($component);
        $this->assertSame([$cacheKey => $compiledViewName], $cache);

        $component::flushCache();

        $cache = (fn () => $component::$bladeViewCache)->call($component);
        $this->assertSame([], $cache);

        $this->assertSame($compiledViewName, $component->resolveView());
        $this->assertSame($compiledViewName, $component->resolveView());
        $this->assertSame($compiledViewName, $component->resolveView());
        $this->assertSame($compiledViewName, $component->resolveView());
    }

    public function testBladeViewCacheWithInlineViewComponentWhereRenderDependsOnProps()
    {
        $componentA = new TestInlineViewComponentWhereRenderDependsOnProps('A');
        $componentB = new TestInlineViewComponentWhereRenderDependsOnProps('B');

        $this->viewFactory->shouldReceive('exists')->twice()->andReturn(false);

        $this->config->shouldReceive('get')->twice()->with('view.compiled')->andReturn('/tmp');

        $this->viewFactory->shouldReceive('addNamespace')
            ->with('__components', '/tmp')
            ->twice();

        $compiledViewNameA = '__components::6dcd4ce23d88e2ee9568ba546c007c63d9131c1b';
        $compiledViewNameB = '__components::ae4f281df5a5d0ff3cad6371f76d5c29b6d953ec';
        $cacheAKey = $componentA::class.'::A';
        $cacheBKey = $componentB::class.'::B';

        $this->assertSame($compiledViewNameA, $componentA->resolveView());
        $this->assertSame($compiledViewNameA, $componentA->resolveView());
        $this->assertSame($compiledViewNameB, $componentB->resolveView());
        $this->assertSame($compiledViewNameB, $componentB->resolveView());

        $cacheA = (fn () => $componentA::$bladeViewCache)->call($componentA);
        $cacheB = (fn () => $componentB::$bladeViewCache)->call($componentB);
        $this->assertSame($cacheA, $cacheB);
        $this->assertSame([
            $cacheAKey => $compiledViewNameA,
            $cacheBKey => $compiledViewNameB,
        ], $cacheA);

        $componentA::flushCache();

        $cacheA = (fn () => $componentA::$bladeViewCache)->call($componentA);
        $cacheB = (fn () => $componentB::$bladeViewCache)->call($componentB);
        $this->assertSame($cacheA, $cacheB);
        $this->assertSame([], $cacheA);
    }

    public function testFactoryGetsSharedBetweenComponents()
    {
        $regular = new TestRegularViewNameViewComponent;
        $inline = new TestInlineViewComponent;

        $getFactory = fn ($component) => (fn () => $component->factory())->call($component);

        $this->assertSame($this->viewFactory, $getFactory($regular));

        Container::getInstance()->instance('view', 'foo');
        $this->assertSame($this->viewFactory, $getFactory($inline));

        Component::forgetFactory();
        $this->assertNotSame($this->viewFactory, $getFactory($inline));
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

class TestInlineViewComponentWithContainerDependencies extends Component
{
    public $dependency;

    public function __construct(FactoryContract $dependency)
    {
        $this->dependency = $dependency;
    }

    public function render()
    {
        return '';
    }
}

class TestInlineViewComponentWithContainerDependenciesAndProps extends Component
{
    public $content;

    public $dependency;

    public function __construct(FactoryContract $dependency, $content)
    {
        $this->content = $content;
        $this->dependency = $dependency;
    }

    public function render()
    {
        return $this->content;
    }
}

class TestInlineViewComponentWithoutDependencies extends Component
{
    public function render()
    {
        return 'alert';
    }
}

class TestInlineViewComponentWhereRenderDependsOnProps extends Component
{
    public $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function render()
    {
        return $this->content;
    }
}

class TestRegularViewComponentUsingViewHelper extends Component
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

class TestRegularViewComponentUsingViewMethod extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function render()
    {
        return $this->view('alert');
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
