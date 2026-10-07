<?php

namespace Illuminate\Tests\View;

use Closure;
use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Translation\Translator;
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
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

class ViewFactoryTest extends TestCase
{
    public function testMakeCreatesNewViewInstanceWithProperPathAndEngine()
    {
        unset($_SERVER['__test.view']);

        $factory = $this->getFactory();
        $factory->getFinder()->expects('find')->with('view')->returns('path.php');
        $engine = Double::for(Engine::class);
        $factory->getEngineResolver()->expects('resolve')->with('php')->returns($engine);
        $factory->getFinder()->expects('addExtension')->with('php');
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
        $factory->getFinder()->expects('find')->with('foo')->throws(new InvalidArgumentException());
        $factory->getFinder()->expects('find')->with('bar')->returns('path.php');

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
        $factory->getFinder()->expects('find')->times(2)->with('view')->returns('path.php');
        $factory->getFinder()->expects('find')->with('bar')->throws(new InvalidArgumentException());
        $engine = Double::for(Engine::class);
        $factory->getEngineResolver()->expects('resolve')->with('php')->returns($engine);
        $factory->getFinder()->expects('addExtension')->with('php');
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
        $factory->getFinder()->expects('find')->with('view')->throws(new InvalidArgumentException());
        $factory->getFinder()->expects('find')->with('bar')->throws(new InvalidArgumentException());
        $engine = Double::for(Engine::class);
        $factory->getFinder()->expects('addExtension')->with('php');
        $factory->addExtension('php', 'php');
        $factory->first(['bar', 'view'], ['foo' => 'bar'], ['baz' => 'boom']);
    }

    public function testRenderEachCreatesViewForEachItemInArray()
    {
        $factory = Double::for(Factory::class)->passthru(new Factory(...$this->getFactoryArgs()));
        $mockView1 = Double::for(ViewContract::class);
        $factory->expects('make')->with('foo', ['key' => 'bar', 'value' => 'baz'])->returns($mockView1);
        $mockView2 = Double::for(ViewContract::class);
        $factory->expects('make')->with('foo', ['key' => 'breeze', 'value' => 'boom'])->returns($mockView2);
        $mockView1->expects('render')->returns('dayle');
        $mockView2->expects('render')->returns('rees');

        $result = $factory->renderEach('foo', ['bar' => 'baz', 'breeze' => 'boom'], 'value');

        $this->assertSame('daylerees', $result);
    }

    public function testEmptyViewsCanBeReturnedFromRenderEach()
    {
        $factory = Double::for(Factory::class)->passthru(new Factory(...$this->getFactoryArgs()));
        $mockView = Double::for(ViewContract::class);
        $factory->expects('make')->with('foo')->returns($mockView);
        $mockView->expects('render')->returns('empty');

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

        $factory->getFinder()->expects('addExtension')->with('foo');
        $factory->getEngineResolver()->expects('register')->with('bar', $resolver);
        $factory->getFinder()->expects('find')->with('view')->returns('path.foo');
        $engine = Double::for(Engine::class);
        $factory->getEngineResolver()->expects('resolve')->with('bar')->returns($engine);
        $factory->getDispatcher()->expects('hasListeners')->returns(false);

        $factory->addExtension('foo', 'bar', $resolver);

        $view = $factory->make('view', ['data']);
        $this->assertSame($engine, $view->getEngine());
    }

    public function testAddingExtensionPrependsNotAppends()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->expects('addExtension')->with('foo');

        $factory->addExtension('foo', 'bar');

        $extensions = $factory->getExtensions();
        $this->assertSame('bar', reset($extensions));
        $this->assertSame('foo', key($extensions));
    }

    public function testPrependedExtensionOverridesExistingExtensions()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->expects('addExtension')->with('foo');
        $factory->getFinder()->expects('addExtension')->with('baz');

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
            ->expects('listen')
            ->with('creating: name', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('creating: name', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('name');

        $factory->creator('name', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingNamespacedWildcards()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('creating: namespaced::*', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('creating: namespaced::my-package-view', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('namespaced::my-package-view');

        $factory->creator('namespaced::*', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingNamespacedNestedWildcards()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('creating: namespaced::*', Argument::type(Closure::class));

        $factory->getDispatcher()
            ->expects('listen')
            ->with('creating: welcome', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('creating: namespaced::my-package-view', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('namespaced::my-package-view');

        $factory->creator(['namespaced::*', 'welcome'], fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingWildcards()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('creating: *', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('creating: name', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('name');

        $factory->creator('*', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallCreatorsDoesDispatchEventsWhenIsNecessaryUsingNormalizedNames()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('creating: components.button', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('creating: components/button', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('components/button');

        $factory->creator('components.button', fn () => true);

        $factory->callCreator($view);
    }

    public function testCallComposerDoesDispatchEventsWhenIsNecessary()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: name', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('composing: name', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('name');

        $factory->composer('name', fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposerDoesDispatchEventsWhenIsNecessaryAndUsingTheArrayFormat()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: name', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('composing: name', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('name');

        $factory->composer(['name'], fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingNamespacedWildcards()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: namespaced::*', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('composing: namespaced::my-package-view', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('namespaced::my-package-view');

        $factory->composer('namespaced::*', fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingNamespacedNestedWildcards()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: namespaced::*', Argument::type(Closure::class));

        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: welcome', Argument::type(Closure::class));

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('composing: namespaced::my-package-view', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('namespaced::my-package-view');

        $factory->composer(['namespaced::*', 'welcome'], fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingWildcards()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: *', Argument::type(Closure::class));

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('composing: name', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('name');

        $factory->composer('*', fn () => true);

        $factory->callComposer($view);
    }

    public function testCallComposersDoesDispatchEventsWhenIsNecessaryUsingNormalizedNames()
    {
        $factory = $this->getFactory();

        $factory->getDispatcher()->expects('hasListeners')->returns(true);

        $factory->getDispatcher()
            ->expects('listen')
            ->with('composing: components.button', Argument::type(Closure::class));

        $factory->getDispatcher()
            ->expects('dispatch')
            ->with('composing: components/button', Argument::type('array'));

        $view = Double::for(View::class);
        $view->expects('name')->returns('components/button');

        $factory->composer('components.button', fn () => true);

        $factory->callComposer($view);
    }

    public function testComposersAreProperlyRegistered()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->expects('listen')->with('composing: foo', Argument::type(Closure::class));
        $callback = $factory->composer('foo', function () {
            return 'bar';
        });
        $callback = $callback[0];

        $this->assertSame('bar', $callback());
    }

    public function testComposersCanBeMassRegistered()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->expects('listen')->with('composing: bar', Argument::type(Closure::class));
        $factory->getDispatcher()->expects('listen')->with('composing: qux', Argument::type(Closure::class));
        $factory->getDispatcher()->expects('listen')->with('composing: foo', Argument::type(Closure::class));
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
        $factory->getDispatcher()->expects('listen')->with('composing: foo', Argument::type(Closure::class));
        $factory->setContainer(new Container);
        $callback = $factory->composer('foo', ViewComposerStub::class);
        $callback = $callback[0];

        $this->assertSame('view composed', $callback('view'));
    }

    public function testClassCallbacksWithMethods()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->expects('listen')->with('composing: foo', Argument::type(Closure::class));
        $factory->setContainer(new Container);
        $callback = $factory->composer('foo', ViewComposerStub::class.'@doComposer');
        $callback = $callback[0];

        $this->assertSame('view do-composed', $callback('view'));
    }

    public function testCallComposerCallsProperEvent()
    {
        $factory = $this->getFactory();
        $view = Double::for(View::class);
        $dispatcher = Double::for(DispatcherContract::class);
        $factory->setDispatcher($dispatcher);

        $dispatcher->expects('listen');

        $view->expects('name')->returns('name');

        $factory->composer('name', fn () => true);

        $factory->getDispatcher()->expects('hasListeners')->returns(true);
        $factory->getDispatcher()->expects('dispatch')->with('composing: name', [$view]);

        $factory->callComposer($view);
    }

    public function testComposersAreRegisteredWithSlashAndDot()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->expects('listen')->with('composing: foo.bar', Argument::any())->times(2);
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
        $view = Double::for(View::class);
        $view->expects('__toString')->returns('<p>hi</p>&lt;p&gt;already escaped&lt;/p&gt;');
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
        $view = Double::for(View::class);
        $view->expects('__toString')->returns('<p>hi</p>&lt;p&gt;already escaped&lt;/p&gt;');
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
        $factory->getFinder()->expects('find')->returns(__DIR__.'/Fixtures/component.php');
        $factory->getEngineResolver()->expects('resolve')->returns(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->expects('hasListeners')->times(2)->returns(false);
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
        $factory->getFinder()->expects('find')->returns(__DIR__.'/Fixtures/component.php');
        $factory->getEngineResolver()->expects('resolve')->returns(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->expects('hasListeners')->times(2)->returns(false);
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
        $factory->getFinder()->expects('find')->returns(__DIR__.'/Fixtures/component.php');
        $factory->getEngineResolver()->expects('resolve')->returns(new PhpEngine(new Filesystem));
        $factory->getDispatcher()->expects('hasListeners')->times(2)->returns(false);
        $factory->startComponent(function ($data) use ($factory) {
            $this->assertArrayHasKey('name', $data);
            $this->assertSame('Taylor', $data['name']);

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

    public function testFlushStateResetsSlots()
    {
        $factory = $this->getFactory();

        $factory->slot('title');
        echo 'laravel.com';
        $factory->endSlot();

        $factory->flushState();

        $this->assertSame([], (fn () => $this->slots)->call($factory));
        $this->assertSame([], (fn () => $this->slotStack)->call($factory));
    }

    public function testTranslation()
    {
        $container = new Container;
        $translator = Double::for(Translator::class);
        $translator->expects('get')->with('Foo', ['name' => 'taylor'])->returns('Bar');
        $container->instance('translator', $translator);
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
        $factory->getFinder()->expects('find')->times(2)->with('foo.bar')->returns('path.php');
        $factory->getEngineResolver()->expects('resolve')->times(2)->with('php')->returns(Double::for(Engine::class));
        $factory->getDispatcher()->expects('hasListeners')->times(2)->returns(false);
        $factory->make('foo/bar');
        $factory->make('foo.bar');
    }

    public function testNamespacedViewNamesAreNormalizedProperly()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->expects('find')->times(2)->with('vendor/package::foo.bar')->returns('path.php');
        $factory->getEngineResolver()->expects('resolve')->times(2)->with('php')->returns(Double::for(Engine::class));
        $factory->getDispatcher()->expects('hasListeners')->times(2)->returns(false);
        $factory->make('vendor/package::foo/bar');
        $factory->make('vendor/package::foo.bar');
    }

    public function testExceptionIsThrownForUnknownExtension()
    {
        $this->expectException(InvalidArgumentException::class);

        $factory = $this->getFactory();
        $factory->getFinder()->expects('find')->with('view')->returns('view.foo');
        $factory->make('view');
    }

    public function testExceptionsInSectionsAreThrown()
    {
        $this->expectExceptionObject(new ErrorException('section exception message'));

        $engine = new CompilerEngine(Double::for(CompilerInterface::class), new Filesystem);
        $engine->getCompiler()->expects('getCompiledPath')->times(2)->resolves(function ($path) {
            return $path;
        });
        $engine->getCompiler()->expects('isExpired')->times(2)->returns(false);
        $factory = $this->getFactory();
        $factory->getEngineResolver()->expects('resolve')->times(2)->returns($engine);
        $factory->getFinder()->expects('find')->with('layout')->returns(__DIR__.'/Fixtures/section-exception-layout.php');
        $factory->getFinder()->expects('find')->with('view')->returns(__DIR__.'/Fixtures/section-exception.php');
        $factory->getDispatcher()->expects('hasListeners')->times(4); // 2 "creating" + 2 "composing"...

        $factory->make('view')->render();
    }

    public function testExtraStopSectionCallThrowsException()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Cannot end a section without first starting one.'));

        $factory = $this->getFactory();
        $factory->startSection('foo');
        $factory->stopSection();

        $factory->stopSection();
    }

    public function testExtraAppendSectionCallThrowsException()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Cannot end a section without first starting one.'));

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
            Double::for(EngineResolver::class),
            Double::for(ViewFinderInterface::class),
            Double::for(DispatcherContract::class)
        );
    }

    protected function getFactoryArgs()
    {
        return [
            Double::for(EngineResolver::class),
            Double::for(ViewFinderInterface::class),
            Double::for(DispatcherContract::class),
        ];
    }
}

class ViewComposerStub
{
    public function compose($view)
    {
        return $view.' composed';
    }

    public function doComposer($view)
    {
        return $view.' do-composed';
    }
}
