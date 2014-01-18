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
		$view = new View(m::mock('Illuminate\View\Factory'), m::mock('Illuminate\View\Engines\EngineInterface'), 'view', 'path', array());
		$view->with('foo', 'bar');
		$view->with(array('baz' => 'boom'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'boom'), $view->getData());


		$view = new View(m::mock('Illuminate\View\Factory'), m::mock('Illuminate\View\Engines\EngineInterface'), 'view', 'path', array());
		$view->withFoo('bar')->withBaz('boom');
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'boom'), $view->getData());
	}


	public function testRenderProperlyRendersView()
	{
		$view = $this->getView();
		$view->getFactory()->shouldReceive('incrementRender')->once()->ordered();
		$view->getFactory()->shouldReceive('callComposer')->once()->ordered()->with($view);
		$view->getFactory()->shouldReceive('getShared')->once()->andReturn(array('shared' => 'foo'));
		$view->getEngine()->shouldReceive('get')->once()->with('path', array('foo' => 'bar', 'shared' => 'foo'))->andReturn('contents');
		$view->getFactory()->shouldReceive('decrementRender')->once()->ordered();
		$view->getFactory()->shouldReceive('flushSectionsIfDoneRendering')->once();

		$me = $this;
		$callback = function(View $rendered, $contents) use ($me, $view)
		{
			$me->assertEquals($view, $rendered);
			$me->assertEquals('contents', $contents);
		};

		$this->assertEquals('contents', $view->render($callback));
	}


	public function testRenderSectionsReturnsEnvironmentSections()
	{
		$view = m::mock('Illuminate\View\View[render]', array(
			m::mock('Illuminate\View\Factory'),
			m::mock('Illuminate\View\Engines\EngineInterface'),
			'view',
			'path',
			array()
		));

		$view->shouldReceive('render')->with(m::type('Closure'))->once()->andReturn($sections = array('foo' => 'bar'));
		$view->getFactory()->shouldReceive('getSections')->once()->andReturn($sections);

		$this->assertEquals($sections, $view->renderSections());
	}


	public function testSectionsAreNotFlushedWhenNotDoneRendering()
	{
		$view = $this->getView();
		$view->getFactory()->shouldReceive('incrementRender')->once();
		$view->getFactory()->shouldReceive('callComposer')->once()->with($view);
		$view->getFactory()->shouldReceive('getShared')->once()->andReturn(array('shared' => 'foo'));
		$view->getEngine()->shouldReceive('get')->once()->with('path', array('foo' => 'bar', 'shared' => 'foo'))->andReturn('contents');
		$view->getFactory()->shouldReceive('decrementRender')->once();
		$view->getFactory()->shouldReceive('flushSectionsIfDoneRendering')->once();

		$this->assertEquals('contents', $view->render());
	}


	public function testViewNestBindsASubView()
	{
		$view = $this->getView();
		$view->getFactory()->shouldReceive('make')->once()->with('foo', array('data'));
		$result = $view->nest('key', 'foo', array('data'));

		$this->assertInstanceOf('Illuminate\View\View', $result);
	}


	public function testViewAcceptsArrayableImplementations()
	{
		$arrayable = m::mock('Illuminate\Support\Contracts\ArrayableInterface');
		$arrayable->shouldReceive('toArray')->once()->andReturn(array('foo' => 'bar', 'baz' => array('qux', 'corge')));

		$view = new View(
			m::mock('Illuminate\View\Factory'),
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
			m::mock('Illuminate\View\Factory'),
			m::mock('Illuminate\View\Engines\EngineInterface'),
			'view',
			'path',
			array('foo' => 'bar')
		);
	}

}
