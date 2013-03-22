<?php

use Mockery as m;
use Illuminate\View\Environment;

class ViewEnvironmentTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeCreatesNewViewInstanceWithProperPathAndEngine()
	{
		$env = $this->getEnvironment();
		$env->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
		$env->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
		$env->getFinder()->shouldReceive('addExtension')->once()->with('php');
		$env->addExtension('php', 'php');
		$view = $env->make('view', array('data'));

		$this->assertTrue($engine === $view->getEngine());
	}


	public function testExistsPassesAndFailsViews()
	{
		$env = $this->getEnvironment();
		$env->getFinder()->shouldReceive('find')->once()->with('foo')->andThrow('InvalidArgumentException');
		$env->getFinder()->shouldReceive('find')->once()->with('bar')->andReturn('path.php');

		$this->assertFalse($env->exists('foo'));
		$this->assertTrue($env->exists('bar'));
	}


	public function testRenderEachCreatesViewForEachItemInArray()
	{
		$env = m::mock('Illuminate\View\Environment[make]', $this->getEnvironmentArgs());
		$env->shouldReceive('make')->once()->with('foo', array('key' => 'bar', 'value' => 'baz'))->andReturn($mockView1 = m::mock('StdClass'));
		$env->shouldReceive('make')->once()->with('foo', array('key' => 'breeze', 'value' => 'boom'))->andReturn($mockView2 = m::mock('StdClass'));
		$mockView1->shouldReceive('render')->once()->andReturn('dayle');
		$mockView2->shouldReceive('render')->once()->andReturn('rees');

		$result = $env->renderEach('foo', array('bar' => 'baz', 'breeze' => 'boom'), 'value');

		$this->assertEquals('daylerees', $result);
	}


	public function testEmptyViewsCanBeReturnedFromRenderEach()
	{
		$env = m::mock('Illuminate\View\Environment[make]', $this->getEnvironmentArgs());
		$env->shouldReceive('make')->once()->with('foo')->andReturn($mockView = m::mock('StdClass'));
		$mockView->shouldReceive('render')->once()->andReturn('empty');

		$this->assertEquals('empty', $env->renderEach('view', array(), 'iterator', 'foo'));
	}


	public function testRawStringsMayBeReturnedFromRenderEach()
	{
		$this->assertEquals('foo', $this->getEnvironment()->renderEach('foo', array(), 'item', 'raw|foo'));
	}


	public function testEnvironmentAddsExtensionWithCustomResolver()
	{
		$environment = $this->getEnvironment();

		$resolver = function(){};

		$environment->getFinder()->shouldReceive('addExtension')->once()->with('foo');
		$environment->getEngineResolver()->shouldReceive('register')->once()->with('bar', $resolver);
		$environment->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.foo');
		$environment->getEngineResolver()->shouldReceive('resolve')->once()->with('bar')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));

		$environment->addExtension('foo', 'bar', $resolver);

		$view = $environment->make('view', array('data'));
		$this->assertTrue($engine === $view->getEngine());
	}


	public function testAddingExtensionPrependsNotAppends()
	{
		$environment = $this->getEnvironment();
		$environment->getFinder()->shouldReceive('addExtension')->once()->with('foo');

		$environment->addExtension('foo', 'bar');

		$extensions = $environment->getExtensions();
		$this->assertEquals('bar', reset($extensions));
		$this->assertEquals('foo', key($extensions));
	}


	public function testPrependedExtensionOverridesExistingExtensions()
	{
		$environment = $this->getEnvironment();
		$environment->getFinder()->shouldReceive('addExtension')->once()->with('foo');
		$environment->getFinder()->shouldReceive('addExtension')->once()->with('baz');

		$environment->addExtension('foo', 'bar');
		$environment->addExtension('baz', 'bar');

		$extensions = $environment->getExtensions();
		$this->assertEquals('bar', reset($extensions));
		$this->assertEquals('baz', key($extensions));
	}


	public function testComposersAreProperlyRegistered()
	{
		$env = $this->getEnvironment();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$callback = $env->composer('foo', function() { return 'bar'; });
		$callback = $callback[0];

		$this->assertEquals('bar', $callback());
	}


	public function testClassCallbacks()
	{
		$env = $this->getEnvironment();
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
		$env = $this->getEnvironment();
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
		$env = $this->getEnvironment();
		$view = m::mock('Illuminate\View\View');
		$view->shouldReceive('getName')->once()->andReturn('name');
		$env->getDispatcher()->shouldReceive('fire')->once()->with('composing: name', array($view));

		$env->callComposer($view);
	}


	public function testRenderCountHandling()
	{
		$env = $this->getEnvironment();
		$env->incrementRender();
		$this->assertFalse($env->doneRendering());
		$env->decrementRender();
		$this->assertTrue($env->doneRendering());
	}


	public function testBasicSectionHandling()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi';
		$environment->stopSection();
		$this->assertEquals('hi', $environment->yieldContent('foo'));
	}


	public function testSectionExtending()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi @parent';
		$environment->stopSection();
		$environment->startSection('foo');
		echo 'there';
		$environment->stopSection();
		$this->assertEquals('hi there', $environment->yieldContent('foo'));
	}


	public function testYieldSectionStopsAndYields()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi';
		$this->assertEquals('hi', $environment->yieldSection());
	}


	public function testInjectStartsSectionWithContent()
	{
		$environment = $this->getEnvironment();
		$environment->inject('foo', 'hi');
		$this->assertEquals('hi', $environment->yieldContent('foo'));
	}


	public function testEmptyStringIsReturnedForNonSections()
	{
		$environment = $this->getEnvironment();
		$this->assertEquals('', $environment->yieldContent('foo'));
	}


	public function testSectionFlushing()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi';
		$environment->stopSection();

		$this->assertEquals(1, count($environment->getSections()));

		$environment->flushSections();

		$this->assertEquals(0, count($environment->getSections()));
	}


	protected function getEnvironment()
	{
		return new Environment(
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
