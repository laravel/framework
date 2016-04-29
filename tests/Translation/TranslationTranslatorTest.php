<?php

use Mockery as m;

class TranslationTranslatorTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testHasMethodReturnsFalseWhenReturnedTranslationIsNull()
    {
        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->will($this->returnValue('foo'));
        $this->assertFalse($t->has('foo', 'bar'));

        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en', 'sp'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->will($this->returnValue('bar'));
        $this->assertTrue($t->has('foo', 'bar'));

        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'), false)->will($this->returnValue('bar'));
        $this->assertTrue($t->hasForLocale('foo', 'bar'));

        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'), false)->will($this->returnValue('foo'));
        $this->assertFalse($t->hasForLocale('foo', 'bar'));

        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['load', 'getLine'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('load')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'))->will($this->returnValue(null));
        $t->expects($this->once())->method('getLine')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'), null, $this->equalTo([]))->will($this->returnValue('bar'));
        $this->assertTrue($t->hasForLocale('foo'));

        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['load', 'getLine'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('load')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'))->will($this->returnValue(null));
        $t->expects($this->once())->method('getLine')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'), null, $this->equalTo([]))->will($this->returnValue('foo'));
        $this->assertFalse($t->hasForLocale('foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItem()
    {
        $t = new Illuminate\Translation\Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo']);
        $this->assertEquals('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertEquals('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemWithLongestReplacementsFirst()
    {
        $t = new Illuminate\Translation\Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo :foobar']);
        $this->assertEquals('breeze bar taylor', $t->get('foo::bar.baz', ['foo' => 'bar', 'foobar' => 'taylor'], 'en'));
        $this->assertEquals('breeze foo bar baz taylor', $t->get('foo::bar.baz', ['foo' => 'foo bar baz', 'foobar' => 'taylor'], 'en'));
        $this->assertEquals('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForGlobalNamespace()
    {
        $t = new Illuminate\Translation\Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'breeze :foo']);
        $this->assertEquals('breeze bar', $t->get('foo.bar', ['foo' => 'bar']));
    }

    public function testChoiceMethodProperlyLoadsAndRetrievesItem()
    {
        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->will($this->returnValue('line'));
        $t->setSelector($selector = m::mock('Symfony\Component\Translation\MessageSelector'));
        $selector->shouldReceive('choose')->once()->with('line', 10, 'en')->andReturn('choiced');

        $t->choice('foo', 10, ['replace']);
    }

    public function testChoiceMethodProperlyCountsCollectionsAndLoadsAndRetrievesItem()
    {
        $t = $this->getMockBuilder('Illuminate\Translation\Translator')->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->exactly(2))->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->will($this->returnValue('line'));
        $t->setSelector($selector = m::mock('Symfony\Component\Translation\MessageSelector'));
        $selector->shouldReceive('choose')->twice()->with('line', 3, 'en')->andReturn('choiced');

        $values = ['foo', 'bar', 'baz'];
        $t->choice('foo', $values, ['replace']);

        $values = new Illuminate\Support\Collection(['foo', 'bar', 'baz']);
        $t->choice('foo', $values, ['replace']);
    }

    protected function getLoader()
    {
        return m::mock('Illuminate\Translation\LoaderInterface');
    }
}
