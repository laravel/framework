<?php

use Mockery as m;
use Illuminate\View\Factory;

class ViewFactoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeCreatesNewViewInstanceWithProperPathAndEngine()
	{
		unset($_SERVER['__test.view']);

		$env = $this->getFactory();
		$env->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
		$env->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
		$env->getFinder()->shouldReceive('addExtension')->once()->with('php');
		$env->setDispatcher(new Illuminate\Events\Dispatcher);
		$env->creator('view', function($view) { $_SERVER['__test.view'] = $view; });
		$env->addExtension('php', 'php');
		$view = $env->make('view', array('foo' => 'bar'), array('baz' => 'boom'));

		$this->assertTrue($engine === $view->getEngine());
		$this->assertTrue($_SERVER['__test.view'] === $view);

		unset($_SERVER['__test.view']);
	}


	public function testExistsPassesAndFailsViews()
	{
		$env = $this->getFactory();
		$env->getFinder()->shouldReceive('find')->once()->with('foo')->andThrow('InvalidArgumentException');
		$env->getFinder()->shouldReceive('find')->once()->with('bar')->andReturn('path.php');

		$this->assertFalse($env->exists('foo'));
		$this->assertTrue($env->exists('bar'));
	}


	public function testRenderEachCreatesViewForEachItemInArray()
	{
		$env = m::mock('Illuminate\View\Factory[make]', $this->getEnvironmentArgs());
		$env->shouldReceive('make')->once()->with('foo', array('key' => 'bar', 'value' => 'baz'))->andReturn($mockView1 = m::mock('StdClass'));
		$env->shouldReceive('make')->once()->with('foo', array('key' => 'breeze', 'value' => 'boom'))->andReturn($mockView2 = m::mock('StdClass'));
		$mockView1->shouldReceive('render')->once()->andReturn('dayle');
		$mockView2->shouldReceive('render')->once()->andReturn('rees');

		$result = $env->renderEach('foo', array('bar' => 'baz', 'breeze' => 'boom'), 'value');

		$this->assertEquals('daylerees', $result);
	}


	public function testEmptyViewsCanBeReturnedFromRenderEach()
	{
		$env = m::mock('Illuminate\View\Factory[make]', $this->getEnvironmentArgs());
		$env->shouldReceive('make')->once()->with('foo')->andReturn($mockView = m::mock('StdClass'));
		$mockView->shouldReceive('render')->once()->andReturn('empty');

		$this->assertEquals('empty', $env->renderEach('view', array(), 'iterator', 'foo'));
	}


    public function testAddANamedViews()
    {
        $env = $this->getFactory();
        $env->name('bar', 'foo');

        $this->assertEquals(array('foo' => 'bar'), $env->getNames());
    }


    public function testMakeAViewFromNamedView()
    {
        $env = $this->getFactory();
        $env->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
        $env->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
        $env->getFinder()->shouldReceive('addExtension')->once()->with('php');
		$env->getDispatcher()->shouldReceive('fire');
        $env->addExtension('php', 'php');
        $env->name('view', 'foo');
        $view = $env->of('foo', array('data'));

        $this->assertTrue($engine === $view->getEngine());
    }


	public function testRawStringsMayBeReturnedFromRenderEach()
	{
		$this->assertEquals('foo', $this->getFactory()->renderEach('foo', array(), 'item', 'raw|foo'));
	}


	public function testFactoryAddsExtensionWithCustomResolver()
	{
		$factory = $this->getFactory();

		$resolver = function(){};

		$factory->getFinder()->shouldReceive('addExtension')->once()->with('foo');
		$factory->getEngineResolver()->shouldReceive('register')->once()->with('bar', $resolver);
		$factory->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.foo');
		$factory->getEngineResolver()->shouldReceive('resolve')->once()->with('bar')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
		$factory->getDispatcher()->shouldReceive('fire');

		$factory->addExtension('foo', 'bar', $resolver);

		$view = $factory->make('view', array('data'));
		$this->assertTrue($engine === $view->getEngine());
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
		$env = $this->getFactory();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$callback = $env->composer('foo', function() { return 'bar'; });
		$callback = $callback[0];

		$this->assertEquals('bar', $callback());
	}


	public function testComposersAreProperlyRegisteredWithPriority()
	{
		$env = $this->getFactory();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'), 1);
		$callback = $env->composer('foo', function() { return 'bar'; }, 1);
		$callback = $callback[0];

		$this->assertEquals('bar', $callback());
	}


	public function testComposersCanBeMassRegistered()
	{
		$env = $this->getFactory();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: bar', m::type('Closure'));
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: qux', m::type('Closure'));
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$composers = $env->composers(array(
			'foo' => 'bar',
			'baz@baz' => array('qux', 'foo'),
		));

		$reflections = array(
			new ReflectionFunction($composers[0]),
			new ReflectionFunction($composers[1]),
		);
		$this->assertEquals(array('class' => 'foo', 'method' => 'compose', 'container' => null), $reflections[0]->getStaticVariables());
		$this->assertEquals(array('class' => 'baz', 'method' => 'baz', 'container' => null), $reflections[1]->getStaticVariables());
	}


	public function testClassCallbacks()
	{
		$env = $this->getFactory();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$env->setContainer($container = m::mock('Illuminate\Container\Container'));
		$container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock('StdClass'));
		$composer->shouldReceive('compose')->once()->with('view')->andReturn('composed');
		$callback = $env->composer('foo', 'FooComposer');
		$callback = $callback[0];

		$this->assertEquals('composed', $callback('view'));
	}


	public function testClassCallbacksWithMethods()
	{
		$env = $this->getFactory();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$env->setContainer($container = m::mock('Illuminate\Container\Container'));
		$container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock('StdClass'));
		$composer->shouldReceive('doComposer')->once()->with('view')->andReturn('composed');
		$callback = $env->composer('foo', 'FooComposer@doComposer');
		$callback = $callback[0];

		$this->assertEquals('composed', $callback('view'));
	}


	public function testCallComposerCallsProperEvent()
	{
		$env = $this->getFactory();
		$view = m::mock('Illuminate\View\View');
		$view->shouldReceive('getName')->once()->andReturn('name');
		$env->getDispatcher()->shouldReceive('fire')->once()->with('composing: name', array($view));

		$env->callComposer($view);
	}


	public function testRenderCountHandling()
	{
		$env = $this->getFactory();
		$env->incrementRender();
		$this->assertFalse($env->doneRendering());
		$env->decrementRender();
		$this->assertTrue($env->doneRendering());
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
		$this->assertEquals('', $factory->yieldContent('foo'));
	}


	public function testSectionFlushing()
	{
		$factory = $this->getFactory();
		$factory->startSection('foo');
		echo 'hi';
		$factory->stopSection();

		$this->assertEquals(1, count($factory->getSections()));

		$factory->flushSections();

		$this->assertEquals(0, count($factory->getSections()));
	}


	protected function getFactory()
	{
		return new Factory(
			m::mock('Illuminate\View\Engines\EngineResolver'),
			m::mock('Illuminate\View\ViewFinderInterface'),
			m::mock('Illuminate\Events\Dispatcher')
		);
	}


	protected function getEnvironmentArgs()
	{
		return array(
			m::mock('Illuminate\View\Engines\EngineResolver'),
			m::mock('Illuminate\View\ViewFinderInterface'),
			m::mock('Illuminate\Events\Dispatcher')
		);
	}

}
