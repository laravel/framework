<?php

namespace Illuminate\Tests\Translation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use Illuminate\Translation\MessageSelector;
use Illuminate\Contracts\Translation\Loader;

class TranslationTranslatorTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testHasMethodReturnsFalseWhenReturnedTranslationIsNull()
    {
        $t = $this->getMockBuilder(Translator::class)->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->will($this->returnValue('foo'));
        $this->assertFalse($t->has('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en', 'sp'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->will($this->returnValue('bar'));
        $this->assertTrue($t->has('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'), false)->will($this->returnValue('bar'));
        $this->assertTrue($t->hasForLocale('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'), false)->will($this->returnValue('foo'));
        $this->assertFalse($t->hasForLocale('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->setMethods(['load', 'getLine'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->any())->method('load')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'))->will($this->returnValue(null));
        $t->expects($this->once())->method('getLine')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'), null, $this->equalTo([]))->will($this->returnValue('bar'));
        $this->assertTrue($t->hasForLocale('foo'));

        $t = $this->getMockBuilder(Translator::class)->setMethods(['load', 'getLine'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->any())->method('load')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'))->will($this->returnValue(null));
        $t->expects($this->once())->method('getLine')->with($this->equalTo('*'), $this->equalTo('foo'), $this->equalTo('en'), null, $this->equalTo([]))->will($this->returnValue('foo'));
        $this->assertFalse($t->hasForLocale('foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo']]);
        $this->assertEquals(['tree bar', 'breeze bar'], $t->get('foo::bar.qux', ['foo' => 'bar'], 'en'));
        $this->assertEquals('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertEquals('foo', $t->get('foo::bar.foo'));
    }

    public function testTransMethodProperlyLoadsAndRetrievesItemWithHTMLInTheMessage()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'breeze <p>test</p>']);
        $this->assertSame('breeze <p>test</p>', $t->trans('foo.bar', [], 'en'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemWithCapitalization()
    {
        $t = $this->getMockBuilder(Translator::class)->setMethods(null)->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :Foo :BAR']);
        $this->assertEquals('breeze Bar FOO', $t->get('foo::bar.baz', ['foo' => 'bar', 'bar' => 'foo'], 'en'));
        $this->assertEquals('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemWithLongestReplacementsFirst()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo :foobar']);
        $this->assertEquals('breeze bar taylor', $t->get('foo::bar.baz', ['foo' => 'bar', 'foobar' => 'taylor'], 'en'));
        $this->assertEquals('breeze foo bar baz taylor', $t->get('foo::bar.baz', ['foo' => 'foo bar baz', 'foobar' => 'taylor'], 'en'));
        $this->assertEquals('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForFallback()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->setFallback('lv');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('lv', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo']);
        $this->assertEquals('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertEquals('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForGlobalNamespace()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'breeze :foo']);
        $this->assertEquals('breeze bar', $t->get('foo.bar', ['foo' => 'bar']));
    }

    public function testChoiceMethodProperlyLoadsAndRetrievesItem()
    {
        $t = $this->getMockBuilder(Translator::class)->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->will($this->returnValue('line'));
        $t->setSelector($selector = m::mock(MessageSelector::class));
        $selector->shouldReceive('choose')->once()->with('line', 10, 'en')->andReturn('choiced');

        $t->choice('foo', 10, ['replace']);
    }

    public function testChoiceMethodProperlyCountsCollectionsAndLoadsAndRetrievesItem()
    {
        $t = $this->getMockBuilder(Translator::class)->setMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->exactly(2))->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->will($this->returnValue('line'));
        $t->setSelector($selector = m::mock(MessageSelector::class));
        $selector->shouldReceive('choose')->twice()->with('line', 3, 'en')->andReturn('choiced');

        $values = ['foo', 'bar', 'baz'];
        $t->choice('foo', $values, ['replace']);

        $values = new Collection(['foo', 'bar', 'baz']);
        $t->choice('foo', $values, ['replace']);
    }

    public function testGetJsonMethod()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['foo' => 'one']);
        $this->assertEquals('one', $t->getFromJson('foo'));
    }

    public function testGetJsonReplaces()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['foo :i:c :u' => 'bar :i:c :u']);
        $this->assertEquals('bar onetwo three', $t->getFromJson('foo :i:c :u', ['i' => 'one', 'c' => 'two', 'u' => 'three']));
    }

    public function testGetJsonReplacesForAssociativeInput()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['foo :i :c' => 'bar :i :c']);
        $this->assertEquals('bar eye see', $t->getFromJson('foo :i :c', ['i' => 'eye', 'c' => 'see']));
    }

    public function testGetJsonPreservesOrder()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['to :name I give :greeting' => ':greeting :name']);
        $this->assertEquals('Greetings David', $t->getFromJson('to :name I give :greeting', ['name' => 'David', 'greeting' => 'Greetings']));
    }

    public function testGetJsonForNonExistingJsonKeyLooksForRegularKeys()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'one']);
        $this->assertEquals('one', $t->getFromJson('foo.bar'));
    }

    public function testGetJsonForNonExistingJsonKeyLooksForRegularKeysAndReplace()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'one :message']);
        $this->assertEquals('one two', $t->getFromJson('foo.bar', ['message' => 'two']));
    }

    public function testGetJsonForNonExistingReturnsSameKey()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'Foo that bar', '*')->andReturn([]);
        $this->assertEquals('Foo that bar', $t->getFromJson('Foo that bar'));
    }

    public function testGetJsonForNonExistingReturnsSameKeyAndReplaces()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo :message', '*')->andReturn([]);
        $this->assertEquals('foo baz', $t->getFromJson('foo :message', ['message' => 'baz']));
    }

    protected function getLoader()
    {
        return m::mock(Loader::class);
    }
}
