<?php

use Mockery as m;
use Illuminate\Translation\Translator;

class TranslationTranslatorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testHasMethodReturnsFalseWhenReturnedTranslationIsNull()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', ['get'], [$this->getLoader(), 'en']);
		$t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->will($this->returnValue('foo'));
		$this->assertFalse($t->has('foo', 'bar'));

		$t = $this->getMock('Illuminate\Translation\Translator', ['get'], [$this->getLoader(), 'en', 'sp']);
		$t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->will($this->returnValue('bar'));
		$this->assertTrue($t->has('foo', 'bar'));
	}


	public function testGetMethodProperlyLoadsAndRetrievesItem()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', null, [$this->getLoader(), 'en']);
		$t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo']);
		$this->assertEquals('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
		$this->assertEquals('foo', $t->get('foo::bar.foo'));
	}


	public function testGetMethodProperlyLoadsAndRetrievesItemWithLongestReplacementsFirst()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', null, [$this->getLoader(), 'en']);
		$t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo :foobar']);
		$this->assertEquals('breeze bar taylor', $t->get('foo::bar.baz', ['foo' => 'bar', 'foobar' => 'taylor'], 'en'));
		$this->assertEquals('foo', $t->get('foo::bar.foo'));
	}


	public function testGetMethodProperlyLoadsAndRetrievesItemForGlobalNamespace()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', null, [$this->getLoader(), 'en']);
		$t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'breeze :foo']);
		$this->assertEquals('breeze bar', $t->get('foo.bar', ['foo' => 'bar']));
	}


	public function testChoiceMethodProperlyLoadsAndRetrievesItem()
	{
		$t = $this->getMock('Illuminate\Translation\Translator', ['get'], [$this->getLoader(), 'en']);
		$t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->will($this->returnValue('line'));
		$t->setSelector($selector = m::mock('Symfony\Component\Translation\MessageSelector'));
		$selector->shouldReceive('choose')->once()->with('line', 10, 'en')->andReturn('choiced');

		$t->choice('foo', 10, ['replace']);
	}


	protected function getLoader()
	{
		return m::mock('Illuminate\Translation\LoaderInterface');
	}

}
