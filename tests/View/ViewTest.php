<?php

use Mockery as m;
use Illuminate\View\View;
use Illuminate\Support\Contracts\ArrayableInterface;

class ViewTest extends PHPUnit_Framework_TestCase {

	public function __construct()
	{
		m::close();
	}


	public function testDataCanBeSetOnView()
	{
		$view = new View(m::mock('Illuminate\View\Environment'), m::mock('Illuminate\View\Engines\EngineInterface'), 'view', 'path', array());
		$view->with('foo', 'bar');
		$view->with(array('baz' => 'boom'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'boom'), $view->getData());
	}


	public function testRenderProperlyRendersView()
	{
		$view = $this->getView();
		$view->getEnvironment()->shouldReceive('incrementRender')->once();
		$view->getEnvironment()->shouldReceive('callComposer')->once()->with($view);
		$view->getEnvironment()->shouldReceive('getShared')->once()->andReturn(array('shared' => 'foo'));
		$view->getEngine()->shouldReceive('get')->once()->with('path', array('foo' => 'bar', 'shared' => 'foo'))->andReturn('contents');
		$view->getEnvironment()->shouldReceive('decrementRender')->once();
		$view->getEnvironment()->shouldReceive('doneRendering')->once()->andReturn(true);
		$view->getEnvironment()->shouldReceive('flushSections')->once();

		$this->assertEquals('contents', $view->render());
	}


	public function testSectionsAreNotFlushedWhenNotDoneRendering()
	{
		$view = $this->getView();
		$view->getEnvironment()->shouldReceive('incrementRender')->once();
		$view->getEnvironment()->shouldReceive('callComposer')->once()->with($view);
		$view->getEnvironment()->shouldReceive('getShared')->once()->andReturn(array('shared' => 'foo'));
		$view->getEngine()->shouldReceive('get')->once()->with('path', array('foo' => 'bar', 'shared' => 'foo'))->andReturn('contents');
		$view->getEnvironment()->shouldReceive('decrementRender')->once();
		$view->getEnvironment()->shouldReceive('doneRendering')->once()->andReturn(false);
		$view->getEnvironment()->shouldReceive('flushSections')->never();

		$this->assertEquals('contents', $view->render());
	}


	public function testViewNestBindsASubView()
	{
		$view = $this->getView();
		$view->getEnvironment()->shouldReceive('make')->once()->with('foo', array('data'));
		$result = $view->nest('key', 'foo', array('data'));

		$this->assertInstanceOf('Illuminate\View\View', $result);
	}


	public function testViewAcceptsArrayableImplementations()
	{
		$arrayable = m::mock('Illuminate\Support\Contracts\ArrayableInterface');
		$arrayable->shouldReceive('toArray')->once()->andReturn(array('foo' => 'bar', 'baz' => array('qux', 'corge')));

		$view = new View(
			m::mock('Illuminate\View\Environment'),
			m::mock('Illuminate\View\Engines\EngineInterface'),
			'view',
			'path',
			$arrayable
		);

		$this->assertEquals('bar', $view->foo);
		$this->assertEquals(array('qux', 'corge'), $view->baz);
	}


	protected function getView()
	{
		return new View(
			m::mock('Illuminate\View\Environment'),
			m::mock('Illuminate\View\Engines\EngineInterface'),
			'view',
			'path',
			array('foo' => 'bar')
		);
	}

}