<?php

namespace Illuminate\Tests\View;

use Closure;
use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\HtmlString;
use Illuminate\Support\LazyCollection;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\View;
use Illuminate\View\ViewFinderInterface;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use stdClass;

class ViewFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMakeCreatesNewViewInstanceWithProperPathAndEngine()
    {
        unset($_SERVER['__test.view']);

        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock(Engine::class));
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('php');
        $factory->setDispatcher(new Dispatcher);
        $factory->creator('view', function ($view) {
            $_SERVER['__test.view'] = $view;
        });
        $factory->addExtension('php', 'php');
        $view = $factory->make('view', ['foo' => 'bar'], ['baz' => 'boom']);

        $this->assertSame($engine, $view->getEngine());
        $this->assertSame($_SERVER['__test.view'], $view);

        unset($_SERVER['__test.view']);
    }

    public function testExistsPassesAndFailsViews()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('foo')->andThrow(InvalidArgumentException::class);
        $factory->getFinder()->shouldReceive('find')->once()->with('bar')->andReturn('path.php');

        $this->assertFalse($factory->exists('foo'));
        $this->assertTrue($factory->exists('bar'));
    }

    public function testRenderingOnceChecks()
    {
        $factory = $this->getFactory();
        $this->assertFalse($factory->hasRenderedOnce('foo'));
        $factory->markAsRenderedOnce('foo');
        $this->assertTrue($factory->hasRenderedOnce('foo'));
        $factory->flushState();
        $this->assertFalse($factory->hasRenderedOnce('foo'));
    }

    public function testFirstCreatesNewViewInstanceWithProperPath()
    {
        unset($_SERVER['__test.view']);

        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->twice()->with('view')->andReturn('path.php');
        $factory->getFinder()->shouldReceive('find')->once()->with('bar')->andThrow(InvalidArgumentException::class);
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock(Engine::class));
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('php');
        $factory->setDispatcher(new Dispatcher);
        $factory->creator('view', function ($view) {
            $_SERVER['__test.view'] = $view;
        });
        $factory->addExtension('php', 'php');
        $view = $factory->first(['bar', 'view'], ['foo' => 'bar'], ['baz' => 'boom']);

        $this->assertInstanceOf(ViewContract::class, $view);
        $this->assertSame($engine, $view->getEngine());
        $this->assertSame($_SERVER['__test.view'], $view);

        unset($_SERVER['__test.view']);
    }

    public function testFirstThrowsInvalidArgumentExceptionIfNoneFound()
    {
        $this->expectException(InvalidArgumentException::class);

        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andThrow(InvalidArgumentException::class);
        $factory->getFinder()->shouldReceive('find')->once()->with('bar')->andThrow(InvalidArgumentException::class);
        $factory->getEngineResolver()->shouldReceive('resolve')->with('php')->andReturn($engine = m::mock(Engine::class));
        $factory->getFinder()->shouldReceive('addExtension')->with('php');
        $factory->addExtension('php', 'php');
        $factory->first(['bar', 'view'], ['foo' => 'bar'], ['baz' => 'boom']);
    }

    public function testRenderEachCreatesViewForEachItemInArray()
    {
        $factory = m::mock(Factory::class.'[make]', $this->getFactoryArgs());
        $factory->shouldReceive('make')->once()->with('foo', ['key' => 'bar', 'value' => 'baz'])->andReturn($mockView1 = m::mock(stdClass::class));
        $factory->shouldReceive('make')->once()->with('foo', ['key' => 'breeze', 'value' => 'boom'])->andReturn($mockView2 = m::mock(stdClass::class));
        $mockView1->shouldReceive('render')->once()->andReturn('dayle');
        $mockView2->shouldReceive('render')->once()->andReturn('rees');

        $result = $factory->renderEach('foo', ['bar' => 'baz', 'breeze' => 'boom'], 'value');

        $this->assertSame('daylerees', $result);
    }

    public function testEmptyViewsCanBeReturnedFromRenderEach()
    {
        $factory = m::mock(Factory::class.'[make]', $this->getFactoryArgs());
        $factory->shouldReceive('make')->once()->with('foo')->andReturn($mockView = m::mock(stdClass::class));
        $mockView->shouldReceive('render')->once()->andReturn('empty');

        $this->assertSame('empty', $factory->renderEach('view', [], 'iterator', 'foo'));
    }

    public function testRawStringsMayBeReturnedFromRenderEach()
    {
        $this->assertSame('foo', $this->getFactory()->renderEach('foo', [], 'item', 'raw|foo'));
    }

    public function testEnvironmentAddsExtensionWithCustomResolver()
    {
        $factory = $this->getFactory();

        $resolver = function () {
            //
        };

        $factory->getFinder()->shouldReceive('addExtension')->once()->with('foo');
        $factory->getEngineResolver()->shouldReceive('register')->once()->with('bar', $resolver);
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.foo');
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('bar')->andReturn($engine = m::mock(Engine::class));
        $factory->getDispatcher()->shouldReceive('dispatch');

        $factory->addExtension('foo', 'bar', $resolver);

        $view = $factory->make('view', ['data']);
        $this->assertSame($engine, $view->getEngine());
    }

    public function testAddingExtensionPrependsNotAppends()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('foo');

        $factory->addExtension('foo', 'bar');

        $extensions = $factory->getExtensions();
        $this->assertSame('bar', reset($extensions));
        $this->assertSame('foo', key($extensions));
    }

    public function testPrependedExtensionOverridesExistingExtensions()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('foo');
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('baz');

        $factory->addExtension('foo', 'bar');
        $factory->addExtension('baz', 'bar');

        $extensions = $factory->getExtensions();
        $this->assertSame('bar', reset($extensions));
        $this->assertSame('baz', key($extensions));
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessary()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('creating: name', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('creating: name', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('name');

        $factory->creator('name', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingNamespacedWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('creating: namespaced::*', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('creating: namespaced::my-package-view', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('namespaced::my-package-view');

        $factory->creator('namespaced::*', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingNamespacedNestedWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('creating: namespaced::*', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('creating: welcome', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('creating: namespaced::my-package-view', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('namespaced::my-package-view');

        $factory->creator(['namespaced::*', 'welcome'], fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('creating: *', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('creating: name', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('name');

        $factory->creator('*', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingNormalizedNames()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('creating: components.button', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('creating: components/button', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')
            ->once()
            ->andReturn('components/button');

        $factory->creator('components.button', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallComposerDoesDispatchEventsWhenIsNecessary()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: name', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('composing: name', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('name');

        $factory->composer('name', fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposerDoesDispatchEventsWhenIsNecessaryAndUsingTheArrayFormat()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: name', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('composing: name', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('name');

        $factory->composer(['name'], fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingNamespacedWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: namespaced::*', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('composing: namespaced::my-package-view', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('namespaced::my-package-view');

        $factory->composer('namespaced::*', fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingNamespacedNestedWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: namespaced::*', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: welcome', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('composing: namespaced::my-package-view', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('namespaced::my-package-view');

        $factory->composer(['namespaced::*', 'welcome'], fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: *', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('composing: name', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('name');

        $factory->composer('*', fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingNormalizedNames()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->shouldReceive('listen')
            ->with('composing: components.button', m::type(Closure::class))
            ->once();

        $factory->getDispatcher()
            ->shouldReceive('dispatch')
            ->with('composing: components/button', m::type('array'))
            ->once();

        $view = m::mock(View::class);
        $view->shouldReceive('name')->once()->andReturn('components/button');

        $factory->composer('components.button', fn () => true);

        $factory->callComposer($view);
    }

    public function testComposersAreProperlyRegistered()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type(Closure::class));
        $callback = $factory->composer('foo', function () {
            return 'bar';
        });
        $callback = $callback[0];

        $this->assertSame('bar', $callback());
    }

    public function testComposersCanBeMassRegistered()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: bar', m::type(Closure::class));
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: qux', m::type(Closure::class));
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type(Closure::class));
        $composers = $factory->composers([
            'foo' => 'bar',
            'baz@baz' => ['qux', 'foo'],
        ]);

        $this->assertCount(3, $composers);
        $reflections = [
            new ReflectionFunction($composers[0]),
            new ReflectionFunction($composers[1]),
        ];
        $this->assertEquals(['class' => 'foo', 'method' => 'compose'], $reflections[0]->getStaticVariables());
        $this->assertEquals(['class' => 'baz', 'method' => 'baz'], $reflections[1]->getStaticVariables());
    }

    public function testClassCallbacks()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type(Closure::class));
        $factory->setContainer($container = m::mock(Container::class));
        $container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock(stdClass::class));
        $composer->shouldReceive('compose')->once()->with('view')->andReturn('composed');
        $callback = $factory->composer('foo', 'FooComposer');
        $callback = $callback[0];

        $this->assertSame('composed', $callback('view'));
    }

    public function testClassCallbacksWithMethods()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type(Closure::class));
        $factory->setContainer($container = m::mock(Container::class));
        $container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock(stdClass::class));
        $composer->shouldReceive('doComposer')->once()->with('view')->andReturn('composed');
        $callback = $factory->composer('foo', 'FooComposer@doComposer');
        $callback = $callback[0];

        $this->assertSame('composed', $callback('view'));
    }

    public function testCallComposerCallsProperEvent()
    {
        $factory = $this->getFactory();
        $view = m::mock(View::class);
        $dispatcher = m::mock(DispatcherContract::class);
        $factory->setDispatcher($dispatcher);

        $dispatcher->shouldReceive('listen', m::any())->once();

        $view->shouldReceive('name')->once()->andReturn('name');

        $factory->composer('name', fn () => true);

        $factory->getDispatcher()->shouldReceive('dispatch')->once()->with('composing: name', [$view]);

        $factory->callComposer($view);
    }

    public function testComposersAreRegisteredWithSlashAndDot()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->with('composing: foo.bar', m::any())->twice();
        $factory->composer('foo.bar', '');
        $factory->composer('foo/bar', '');
    }

    public function testRenderCountHandling()
    {
        $factory = $this->getFactory();
        $factory->incrementRender();
        $this->assertFalse($factory->doneRendering());
        $factory->decrementRender();
        $this->assertTrue($factory->doneRendering());
    }

    public function testYieldDefault()
    {
        $factory = $this->getFactory();
        $this->assertSame('hi', $factory->yieldContent('foo', 'hi'));
    }

    public function testYieldDefaultIsEscaped()
    {
        $factory = $this->getFactory();
        $this->assertSame('&lt;p&gt;hi&lt;/p&gt;', $factory->yieldContent('foo', '<p>hi</p>'));
    }

    public function testYieldDefaultViewIsNotEscapedTwice()
    {
        $factory = $this->getFactory();
        $view = m::mock(View::class);
        $view->shouldReceive('__toString')->once()->andReturn('<p>hi</p>&lt;p&gt;already escaped&lt;/p&gt;');
        $this->assertSame('<p>hi</p>&lt;p&gt;already escaped&lt;/p&gt;', $factory->yieldContent('foo', $view));
    }

    public function testBasicFragmentHandling()
    {
        $factory = $this->getFactory();
        $factory->startFragment('foo');
        echo 'hi';
        $this->assertSame('hi', $factory->stopFragment());
    }

    public function testBasicSectionHandling()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->stopSection();
        $this->assertSame('hi', $factory->yieldContent('foo'));
    }

    public function testBasicSectionDefault()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo', 'hi');
        $this->assertSame('hi', $factory->yieldContent('foo'));
    }

    public function testBasicSectionDefaultIsEscaped()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo', '<p>hi</p>');
        $this->assertSame('&lt;p&gt;hi&lt;/p&gt;', $factory->yieldContent('foo'));
    }

    public function testBasicSectionDefaultViewIsNotEscapedTwice()
    {
        $factory = $this->getFactory();
        $view = m::mock(View::class);
        $view->shouldReceive('__toString')->once()->andReturn('<p>hi</p>&lt;p&gt;already escaped&lt;/p&gt;');
        $factory->startSection('foo', $view);
        $this->assertSame('<p>hi</p>&lt;p&gt;already escaped&lt;/p&gt;', $factory->yieldContent('foo'));
    }

    public function testSectionExtending()
    {
        $placeholder = Factory::parentPlaceholder('foo');
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi '.$placeholder;
        $factory->stopSection();
        $factory->startSection('foo');
        echo 'there';
        $factory->stopSection();
        $this->assertSame('hi there', $factory->yieldContent('foo'));
    }

    public function testSectionMultipleExtending()
    {
        $placeholder = Factory::parentPlaceholder('foo');
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hello '.$placeholder.' nice to see you '.$placeholder;
        $factory->stopSection();
        $factory->startSection('foo');
        echo 'my '.$placeholder;
        $factory->stopSection();
        $factory->startSection('foo');
        echo 'friend';
        $factory->stopSection();
        $this->assertSame('hello my friend nice to see you my friend', $factory->yieldContent('foo'));
    }

    public function testComponentHandling()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->andReturn(__DIR__.'/fixtures/component.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->shouldReceive('dispatch');
        $factory->startComponent('component', ['name' => 'Taylor']);
        $factory->slot('title');
        $factory->slot('website', 'laravel.com', []);
        echo 'title<hr>';
        $factory->endSlot();
        echo 'component';
        $contents = $factory->renderComponent();
        $this->assertSame('title<hr> component Taylor laravel.com', $contents);
    }

    public function testComponentHandlingUsingViewObject()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->andReturn(__DIR__.'/fixtures/component.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->shouldReceive('dispatch');
        $factory->startComponent($factory->make('component'), ['name' => 'Taylor']);
        $factory->slot('title');
        $factory->slot('website', 'laravel.com', []);
        echo 'title<hr>';
        $factory->endSlot();
        echo 'component';
        $contents = $factory->renderComponent();
        $this->assertSame('title<hr> component Taylor laravel.com', $contents);
    }

    public function testComponentHandlingUsingClosure()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->andReturn(__DIR__.'/fixtures/component.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->shouldReceive('dispatch');
        $factory->startComponent(function ($data) use ($factory) {
            $this->assertArrayHasKey('name', $data);
            $this->assertSame($data['name'], 'Taylor');

            return $factory->make('component');
        }, ['name' => 'Taylor']);
        $factory->slot('title');
        $factory->slot('website', 'laravel.com', []);
        echo 'title<hr>';
        $factory->endSlot();
        echo 'component';
        $contents = $factory->renderComponent();
        $this->assertSame('title<hr> component Taylor laravel.com', $contents);
    }

    public function testComponentHandlingUsingHtmlable()
    {
        $factory = $this->getFactory();
        $factory->startComponent(new HtmlString('laravel.com'));
        $contents = $factory->renderComponent();
        $this->assertSame('laravel.com', $contents);
    }

    public function testTranslation()
    {
        $container = new Container;
        $container->instance('translator', $translator = m::mock(stdClass::class));
        $translator->shouldReceive('get')->with('Foo', ['name' => 'taylor'])->andReturn('Bar');
        $factory = $this->getFactory();
        $factory->setContainer($container);
        $factory->startTranslation(['name' => 'taylor']);
        echo 'Foo';
        $string = $factory->renderTranslation();

        $this->assertSame('Bar', $string);
    }

    public function testSingleStackPush()
    {
        $factory = $this->getFactory();
        $factory->startPush('foo');
        echo 'hi';
        $factory->stopPush();
        $this->assertSame('hi', $factory->yieldPushContent('foo'));
    }

    public function testMultipleStackPush()
    {
        $factory = $this->getFactory();
        $factory->startPush('foo');
        echo 'hi';
        $factory->stopPush();
        $factory->startPush('foo');
        echo ', Hello!';
        $factory->stopPush();
        $this->assertSame('hi, Hello!', $factory->yieldPushContent('foo'));
    }

    public function testSingleStackPrepend()
    {
        $factory = $this->getFactory();
        $factory->startPrepend('foo');
        echo 'hi';
        $factory->stopPrepend();
        $this->assertSame('hi', $factory->yieldPushContent('foo'));
    }

    public function testMultipleStackPrepend()
    {
        $factory = $this->getFactory();
        $factory->startPrepend('foo');
        echo ', Hello!';
        $factory->stopPrepend();
        $factory->startPrepend('foo');
        echo 'hi';
        $factory->stopPrepend();
        $this->assertSame('hi, Hello!', $factory->yieldPushContent('foo'));
    }

    public function testSessionAppending()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->appendSection();
        $factory->startSection('foo');
        echo 'there';
        $factory->appendSection();
        $this->assertSame('hithere', $factory->yieldContent('foo'));
    }

    public function testYieldSectionStopsAndYields()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $this->assertSame('hi', $factory->yieldSection());
    }

    public function testInjectStartsSectionWithContent()
    {
        $factory = $this->getFactory();
        $factory->inject('foo', 'hi');
        $this->assertSame('hi', $factory->yieldContent('foo'));
    }

    public function testEmptyStringIsReturnedForNonSections()
    {
        $factory = $this->getFactory();
        $this->assertEmpty($factory->yieldContent('foo'));
    }

    public function testSectionFlushing()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->stopSection();

        $this->assertCount(1, $factory->getSections());

        $factory->flushSections();

        $this->assertCount(0, $factory->getSections());
    }

    public function testHasSection()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->stopSection();

        $this->assertTrue($factory->hasSection('foo'));
        $this->assertFalse($factory->hasSection('bar'));
    }

    public function testSectionMissing()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hello world';
        $factory->stopSection();

        $this->assertTrue($factory->sectionMissing('bar'));
        $this->assertFalse($factory->sectionMissing('foo'));
    }

    public function testGetSection()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->stopSection();

        $this->assertSame('hi', $factory->getSection('foo'));
        $this->assertNull($factory->getSection('bar'));
        $this->assertSame('default', $factory->getSection('bar', 'default'));
    }

    public function testMakeWithSlashAndDot()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->twice()->with('foo.bar')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->twice()->with('php')->andReturn(m::mock(Engine::class));
        $factory->getDispatcher()->shouldReceive('dispatch');
        $factory->make('foo/bar');
        $factory->make('foo.bar');
    }

    public function testNamespacedViewNamesAreNormalizedProperly()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->twice()->with('vendor/package::foo.bar')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->twice()->with('php')->andReturn(m::mock(Engine::class));
        $factory->getDispatcher()->shouldReceive('dispatch');
        $factory->make('vendor/package::foo/bar');
        $factory->make('vendor/package::foo.bar');
    }

    public function testExceptionIsThrownForUnknownExtension()
    {
        $this->expectException(InvalidArgumentException::class);

        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('view.foo');
        $factory->make('view');
    }

    public function testExceptionsInSectionsAreThrown()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('section exception message');

        $engine = new CompilerEngine(m::mock(CompilerInterface::class), new Filesystem);
        $engine->getCompiler()->shouldReceive('getCompiledPath')->andReturnUsing(function ($path) {
            return $path;
        });
        $engine->getCompiler()->shouldReceive('isExpired')->twice()->andReturn(false);
        $factory = $this->getFactory();
        $factory->getEngineResolver()->shouldReceive('resolve')->twice()->andReturn($engine);
        $factory->getFinder()->shouldReceive('find')->once()->with('layout')->andReturn(__DIR__.'/fixtures/section-exception-layout.php');
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn(__DIR__.'/fixtures/section-exception.php');
        $factory->getDispatcher()->shouldReceive('dispatch')->times(4); // 2 "creating" + 2 "composing"...

        $factory->make('view')->render();
    }

    public function testExtraStopSectionCallThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot end a section without first starting one.');

        $factory = $this->getFactory();
        $factory->startSection('foo');
        $factory->stopSection();

        $factory->stopSection();
    }

    public function testExtraAppendSectionCallThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot end a section without first starting one.');

        $factory = $this->getFactory();
        $factory->startSection('foo');
        $factory->stopSection();

        $factory->appendSection();
    }

    public function testAddingLoops()
    {
        $factory = $this->getFactory();

        $factory->addLoop([1, 2, 3]);

        $expectedLoop = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => 3,
            'count' => 3,
            'first' => true,
            'last' => false,
            'odd' => false,
            'even' => true,
            'depth' => 1,
            'parent' => null,
        ];

        $this->assertEquals([$expectedLoop], $factory->getLoopStack());

        $factory->addLoop([1, 2, 3, 4]);

        $secondExpectedLoop = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => 4,
            'count' => 4,
            'first' => true,
            'last' => false,
            'odd' => false,
            'even' => true,
            'depth' => 2,
            'parent' => (object) $expectedLoop,
        ];
        $this->assertEquals([$expectedLoop, $secondExpectedLoop], $factory->getLoopStack());

        $factory->popLoop();

        $this->assertEquals([$expectedLoop], $factory->getLoopStack());
    }

    public function testAddingLoopDoesNotCloseGenerator()
    {
        $factory = $this->getFactory();

        $data = (new class
        {
            public function generate()
            {
                for ($count = 0; $count < 3; $count++) {
                    yield ['a', 'b'];
                }
            }
        })->generate();

        $factory->addLoop($data);

        foreach ($data as $chunk) {
            $this->assertEquals(['a', 'b'], $chunk);
        }
    }

    public function testAddingUncountableLoop()
    {
        $factory = $this->getFactory();

        $factory->addLoop('');

        $expectedLoop = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => null,
            'count' => null,
            'first' => true,
            'last' => null,
            'odd' => false,
            'even' => true,
            'depth' => 1,
            'parent' => null,
        ];

        $this->assertEquals([$expectedLoop], $factory->getLoopStack());
    }

    public function testAddingLazyCollection()
    {
        $factory = $this->getFactory();

        $factory->addLoop(new LazyCollection(function () {
            $this->fail('LazyCollection\'s generator should not have been called');
        }));

        $expectedLoop = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => null,
            'count' => null,
            'first' => true,
            'last' => null,
            'odd' => false,
            'even' => true,
            'depth' => 1,
            'parent' => null,
        ];

        $this->assertEquals([$expectedLoop], $factory->getLoopStack());
    }

    public function testIncrementingLoopIndices()
    {
        $factory = $this->getFactory();

        $factory->addLoop([1, 2, 3, 4]);

        $factory->incrementLoopIndices();

        $this->assertEquals(1, $factory->getLoopStack()[0]['iteration']);
        $this->assertEquals(0, $factory->getLoopStack()[0]['index']);
        $this->assertEquals(3, $factory->getLoopStack()[0]['remaining']);
        $this->assertTrue($factory->getLoopStack()[0]['odd']);
        $this->assertFalse($factory->getLoopStack()[0]['even']);

        $factory->incrementLoopIndices();

        $this->assertEquals(2, $factory->getLoopStack()[0]['iteration']);
        $this->assertEquals(1, $factory->getLoopStack()[0]['index']);
        $this->assertEquals(2, $factory->getLoopStack()[0]['remaining']);
        $this->assertFalse($factory->getLoopStack()[0]['odd']);
        $this->assertTrue($factory->getLoopStack()[0]['even']);
    }

    public function testReachingEndOfLoop()
    {
        $factory = $this->getFactory();

        $factory->addLoop([1, 2]);

        $factory->incrementLoopIndices();

        $factory->incrementLoopIndices();

        $this->assertTrue($factory->getLoopStack()[0]['last']);
    }

    public function testIncrementingLoopIndicesOfUncountable()
    {
        $factory = $this->getFactory();

        $factory->addLoop('');

        $factory->incrementLoopIndices();

        $factory->incrementLoopIndices();

        $this->assertEquals(2, $factory->getLoopStack()[0]['iteration']);
        $this->assertEquals(1, $factory->getLoopStack()[0]['index']);
        $this->assertFalse($factory->getLoopStack()[0]['first']);
        $this->assertNull($factory->getLoopStack()[0]['remaining']);
        $this->assertNull($factory->getLoopStack()[0]['last']);
    }

    public function testMacro()
    {
        $factory = $this->getFactory();
        $factory->macro('getFoo', function () {
            return 'Hello World';
        });
        $this->assertSame('Hello World', $factory->getFoo());
    }

    protected function getFactory()
    {
        return new Factory(
            m::mock(EngineResolver::class),
            m::mock(ViewFinderInterface::class),
            m::mock(DispatcherContract::class)
        );
    }

    protected function getFactoryArgs()
    {
        return [
            m::mock(EngineResolver::class),
            m::mock(ViewFinderInterface::class),
            m::mock(DispatcherContract::class),
        ];
    }
}
