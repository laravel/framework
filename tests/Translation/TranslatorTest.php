<?php

use Mockery as m;
use Illuminate\Translation\Translator;

class TranslatorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSymfonyTranslatorIsCreated()
	{
		$t = new Translator($loader = $this->getLoader(), 'en', 'sp');
		$this->assertEquals('en', $t->getSymfonyTranslator()->getLocale());
	}


	public function testHasMethodReturnsFalseWhenReturnedTranslationEqualsKey()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', array('get'), array($this->getLoader(), 'en', 'sp'));
		$t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo(array()), $this->equalTo('bar'))->will($this->returnValue('foo'));
		$this->assertFalse($t->has('foo', 'bar'));

		$t = $this->getMock('Illuminate\Translation\Translator', array('get'), array($this->getLoader(), 'en', 'sp'));
		$t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo(array()), $this->equalTo('bar'))->will($this->returnValue('baz'));
		$this->assertTrue($t->has('foo', 'bar'));
	}


	public function testGetMethodProperlyLoadsAndRetrievesItem()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', array('load', 'trans'), array($this->getLoader(), 'en', 'sp'));
		$t->expects($this->once())->method('load')->with($this->equalTo('bar'), $this->equalTo('foo'), $this->equalTo('en'))->will($this->returnValue('foo::bar'));
		$t->setSymfonyTranslator($base = m::mock('Illuminate\Translation\SymfonyTranslator'));
		$base->shouldReceive('trans')->once()->with('baz', array('foo'), 'foo::bar', 'en')->andReturn('breeze');

		$this->assertEquals('breeze', $t->get('foo::bar.baz', array('foo'), 'en'));
	}


	public function testChoiceMethodProperlyLoadsAndRetrievesItem()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', array('load', 'transChoice'), array($this->getLoader(), 'en', 'sp'));
		$t->expects($this->once())->method('load')->with($this->equalTo('bar'), $this->equalTo('foo'), $this->equalTo('en'))->will($this->returnValue('foo::bar'));
		$t->setSymfonyTranslator($base = m::mock('Illuminate\Translation\SymfonyTranslator'));
		$base->shouldReceive('transChoice')->once()->with('baz', 10, array('foo'), 'foo::bar', 'en')->andReturn('breeze');

		$this->assertEquals('breeze', $t->choice('foo::bar.baz', 10, array('foo'), 'en'));
	}


	public function testLoadMethodProperlyCallsLoaderToRetrieveItems()
	{
		$t = new Translator($loader = $this->getLoader(), 'en', 'sp');
		$loader->shouldReceive('load')->once()->with('en', 'foo', 'bar')->andReturn(array('messages' => array('foo' => 'bar')));
		$t->setSymfonyTranslator($base = m::mock('Illuminate\Translation\SymfonyTranslator'));
		$base->shouldReceive('addResource')->once()->with('array', array('messages.foo' => 'bar'), 'en', 'bar::foo');
		$base->shouldReceive('refreshCatalogue')->once()->with('en');
		$base->shouldReceive('getLocale')->andReturn('en');
		$domain = $t->load('foo', 'bar', null);

		$this->assertEquals('bar::foo', $domain);

		// Call load again to make sure the loader is only called once...
		$t->load('foo', 'bar', null);
	}


	public function testKeyIsReturnedThroughTransMethodsWhenItemsDontExist()
	{
		$t = new Translator($loader = $this->getLoader(), 'en', 'sp');
		$loader->shouldReceive('load')->once()->andReturn(array());
		$t->setSymfonyTranslator($base = m::mock('Illuminate\Translation\SymfonyTranslator'));
		$base->shouldReceive('getLocale')->andReturn('en');
		$base->shouldReceive('addResource');
		$base->shouldReceive('refreshCatalogue')->once()->with('en');
		$base->shouldReceive('trans')->once()->with('bar', array(), '::foo', null)->andReturn('bar');

		$this->assertEquals('foo.bar', $t->trans('foo.bar'));
	}


	protected function getLoader()
	{
		return m::mock('Illuminate\Translation\LoaderInterface');
	}

}