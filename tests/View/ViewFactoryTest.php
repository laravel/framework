<?php

use Mockery as m;
use Illuminate\View\Factory;

class ViewFactoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMakeCreatesNewViewInstanceWithProperPathAndEngine()
    {
        unset($_SERVER['__test.view']);

        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('php');
        $factory->setDispatcher(new Illuminate\Events\Dispatcher);
        $factory->creator('view', function ($view) { $_SERVER['__test.view'] = $view; });
        $factory->addExtension('php', 'php');
        $view = $factory->make('view', ['foo' => 'bar'], ['baz' => 'boom']);

        $this->assertSame($engine, $view->getEngine());
        $this->assertSame($_SERVER['__test.view'], $view);

        unset($_SERVER['__test.view']);
    }

    public function testExistsPassesAndFailsViews()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('foo')->andThrow('InvalidArgumentException');
        $factory->getFinder()->shouldReceive('find')->once()->with('bar')->andReturn('path.php');

        $this->assertFalse($factory->exists('foo'));
        $this->assertTrue($factory->exists('bar'));
    }

    public function testRenderEachCreatesViewForEachItemInArray()
    {
        $factory = m::mock('Illuminate\View\Factory[make]', $this->getFactoryArgs());
        $factory->shouldReceive('make')->once()->with('foo', ['key' => 'bar', 'value' => 'baz'])->andReturn($mockView1 = m::mock('StdClass'));
        $factory->shouldReceive('make')->once()->with('foo', ['key' => 'breeze', 'value' => 'boom'])->andReturn($mockView2 = m::mock('StdClass'));
        $mockView1->shouldReceive('render')->once()->andReturn('dayle');
        $mockView2->shouldReceive('render')->once()->andReturn('rees');

        $result = $factory->renderEach('foo', ['bar' => 'baz', 'breeze' => 'boom'], 'value');

        $this->assertEquals('daylerees', $result);
    }

    public function testEmptyViewsCanBeReturnedFromRenderEach()
    {
        $factory = m::mock('Illuminate\View\Factory[make]', $this->getFactoryArgs());
        $factory->shouldReceive('make')->once()->with('foo')->andReturn($mockView = m::mock('StdClass'));
        $mockView->shouldReceive('render')->once()->andReturn('empty');

        $this->assertEquals('empty', $factory->renderEach('view', [], 'iterator', 'foo'));
    }

    public function testAddANamedViews()
    {
        $factory = $this->getFactory();
        $factory->name('bar', 'foo');

        $this->assertEquals(['foo' => 'bar'], $factory->getNames());
    }

    public function testMakeAViewFromNamedView()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('php');
        $factory->getDispatcher()->shouldReceive('fire');
        $factory->addExtension('php', 'php');
        $factory->name('view', 'foo');
        $view = $factory->of('foo', ['data']);

        $this->assertSame($engine, $view->getEngine());
    }

    public function testRawStringsMayBeReturnedFromRenderEach()
    {
        $this->assertEquals('foo', $this->getFactory()->renderEach('foo', [], 'item', 'raw|foo'));
    }

    public function testEnvironmentAddsExtensionWithCustomResolver()
    {
        $factory = $this->getFactory();

        $resolver = function () {};

        $factory->getFinder()->shouldReceive('addExtension')->once()->with('foo');
        $factory->getEngineResolver()->shouldReceive('register')->once()->with('bar', $resolver);
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.foo');
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('bar')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
        $factory->getDispatcher()->shouldReceive('fire');

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
        $this->assertEquals('bar', reset($extensions));
        $this->assertEquals('foo', key($extensions));
    }

    public function testPrependedExtensionOverridesExistingExtensions()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('foo');
        $factory->getFinder()->shouldReceive('addExtension')->once()->with('baz');

        $factory->addExtension('foo', 'bar');
        $factory->addExtension('baz', 'bar');

        $extensions = $factory->getExtensions();
        $this->assertEquals('bar', reset($extensions));
        $this->assertEquals('baz', key($extensions));
    }

    public function testComposersAreProperlyRegistered()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
        $callback = $factory->composer('foo', function () { return 'bar'; });
        $callback = $callback[0];

        $this->assertEquals('bar', $callback());
    }

    public function testComposersAreProperlyRegisteredWithPriority()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'), 1);
        $callback = $factory->composer('foo', function () { return 'bar'; }, 1);
        $callback = $callback[0];

        $this->assertEquals('bar', $callback());
    }

    public function testComposersCanBeMassRegistered()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: bar', m::type('Closure'));
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: qux', m::type('Closure'));
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
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
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
        $factory->setContainer($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock('StdClass'));
        $composer->shouldReceive('compose')->once()->with('view')->andReturn('composed');
        $callback = $factory->composer('foo', 'FooComposer');
        $callback = $callback[0];

        $this->assertEquals('composed', $callback('view'));
    }

    public function testClassCallbacksWithMethods()
    {
        $factory = $this->getFactory();
        $factory->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
        $factory->setContainer($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock('StdClass'));
        $composer->shouldReceive('doComposer')->once()->with('view')->andReturn('composed');
        $callback = $factory->composer('foo', 'FooComposer@doComposer');
        $callback = $callback[0];

        $this->assertEquals('composed', $callback('view'));
    }

    public function testCallComposerCallsProperEvent()
    {
        $factory = $this->getFactory();
        $view = m::mock('Illuminate\View\View');
        $view->shouldReceive('getName')->once()->andReturn('name');
        $factory->getDispatcher()->shouldReceive('fire')->once()->with('composing: name', [$view]);

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

    public function testBasicSectionHandling()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->stopSection();
        $this->assertEquals('hi', $factory->yieldContent('foo'));
    }

    public function testSectionExtending()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi @parent';
        $factory->stopSection();
        $factory->startSection('foo');
        echo 'there';
        $factory->stopSection();
        $this->assertEquals('hi there', $factory->yieldContent('foo'));
    }

    public function testSectionMultipleExtending()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hello @parent nice to see you @parent';
        $factory->stopSection();
        $factory->startSection('foo');
        echo 'my @parent';
        $factory->stopSection();
        $factory->startSection('foo');
        echo 'friend';
        $factory->stopSection();
        $this->assertEquals('hello my friend nice to see you my friend', $factory->yieldContent('foo'));
    }

    public function testSingleStackPush()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->appendSection();
        $this->assertEquals('hi', $factory->yieldContent('foo'));
    }

    public function testMultipleStackPush()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $factory->appendSection();
        $factory->startSection('foo');
        echo ', Hello!';
        $factory->appendSection();
        $this->assertEquals('hi, Hello!', $factory->yieldContent('foo'));
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
        $this->assertEquals('hithere', $factory->yieldContent('foo'));
    }

    public function testYieldSectionStopsAndYields()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        echo 'hi';
        $this->assertEquals('hi', $factory->yieldSection());
    }

    public function testInjectStartsSectionWithContent()
    {
        $factory = $this->getFactory();
        $factory->inject('foo', 'hi');
        $this->assertEquals('hi', $factory->yieldContent('foo'));
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

    public function testMakeWithSlashAndDot()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->twice()->with('foo.bar')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->twice()->with('php')->andReturn(m::mock('Illuminate\View\Engines\EngineInterface'));
        $factory->getDispatcher()->shouldReceive('fire');
        $factory->make('foo/bar');
        $factory->make('foo.bar');
    }

    public function testNamespacedViewNamesAreNormalizedProperly()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->twice()->with('vendor/package::foo.bar')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->twice()->with('php')->andReturn(m::mock('Illuminate\View\Engines\EngineInterface'));
        $factory->getDispatcher()->shouldReceive('fire');
        $factory->make('vendor/package::foo/bar');
        $factory->make('vendor/package::foo.bar');
    }

    public function testMakeWithAlias()
    {
        $factory = $this->getFactory();
        $factory->alias('real', 'alias');
        $factory->getFinder()->shouldReceive('find')->once()->with('real')->andReturn('path.php');
        $factory->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn(m::mock('Illuminate\View\Engines\EngineInterface'));
        $factory->getDispatcher()->shouldReceive('fire');

        $view = $factory->make('alias');

        $this->assertEquals('real', $view->getName());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownForUnknownExtension()
    {
        $factory = $this->getFactory();
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('view.foo');
        $factory->make('view');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage section exception message
     */
    public function testExceptionsInSectionsAreThrown()
    {
        $engine = new Illuminate\View\Engines\CompilerEngine(m::mock('Illuminate\View\Compilers\CompilerInterface'));
        $engine->getCompiler()->shouldReceive('getCompiledPath')->andReturnUsing(function ($path) { return $path; });
        $engine->getCompiler()->shouldReceive('isExpired')->twice()->andReturn(false);
        $factory = $this->getFactory();
        $factory->getEngineResolver()->shouldReceive('resolve')->twice()->andReturn($engine);
        $factory->getFinder()->shouldReceive('find')->once()->with('layout')->andReturn(__DIR__.'/fixtures/section-exception-layout.php');
        $factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn(__DIR__.'/fixtures/section-exception.php');
        $factory->getDispatcher()->shouldReceive('fire')->times(4);

        $factory->make('view')->render();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Cannot end a section without first starting one.
     */
    public function testExtraStopSectionCallThrowsException()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        $factory->stopSection();

        $factory->stopSection();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Cannot end a section without first starting one.
     */
    public function testExtraAppendSectionCallThrowsException()
    {
        $factory = $this->getFactory();
        $factory->startSection('foo');
        $factory->stopSection();

        $factory->appendSection();
    }

    protected function getFactory()
    {
        return new Factory(
            m::mock('Illuminate\View\Engines\EngineResolver'),
            m::mock('Illuminate\View\ViewFinderInterface'),
            m::mock('Illuminate\Contracts\Events\Dispatcher')
        );
    }

    protected function getFactoryArgs()
    {
        return [
            m::mock('Illuminate\View\Engines\EngineResolver'),
            m::mock('Illuminate\View\ViewFinderInterface'),
            m::mock('Illuminate\Contracts\Events\Dispatcher'),
        ];
    }
}
