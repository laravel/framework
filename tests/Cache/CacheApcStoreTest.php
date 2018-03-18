<?php

namespace Illuminate\Tests\Cache;

use PHPUnit\Framework\TestCase;

class CacheApcStoreTest extends TestCase
{
    public function testGetReturnsNullWhenNotFound(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->will($this->returnValue(null));
        $store = new \Illuminate\Cache\ApcStore($apc, 'foo');
        $this->assertNull($store->get('bar'));
    }

    public function testAPCValueIsReturned(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->will($this->returnValue('bar'));
        $store = new \Illuminate\Cache\ApcStore($apc);
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testGetMultipleReturnsNullWhenNotFoundAndValueWhenFound(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['get'])->getMock();
        $apc->expects($this->exactly(3))->method('get')->willReturnMap([
            ['foo', 'qux'],
            ['bar', null],
            ['baz', 'norf'],
        ]);
        $store = new \Illuminate\Cache\ApcStore($apc);
        $this->assertEquals([
            'foo'   => 'qux',
            'bar'   => null,
            'baz'   => 'norf',
        ], $store->many(['foo', 'bar', 'baz']));
    }

    public function testSetMethodProperlyCallsAPC(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['put'])->getMock();
        $apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
        $store = new \Illuminate\Cache\ApcStore($apc);
        $store->put('foo', 'bar', 1);
    }

    public function testSetMultipleMethodProperlyCallsAPC(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['put'])->getMock();
        $apc->expects($this->exactly(3))->method('put')->withConsecutive([
            $this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60),
        ], [
            $this->equalTo('baz'), $this->equalTo('qux'), $this->equalTo(60),
        ], [
            $this->equalTo('bar'), $this->equalTo('norf'), $this->equalTo(60),
        ]);
        $store = new \Illuminate\Cache\ApcStore($apc);
        $store->putMany([
            'foo'   => 'bar',
            'baz'   => 'qux',
            'bar'   => 'norf',
        ], 1);
    }

    public function testIncrementMethodProperlyCallsAPC(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['increment'])->getMock();
        $apc->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new \Illuminate\Cache\ApcStore($apc);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsAPC(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['decrement'])->getMock();
        $apc->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new \Illuminate\Cache\ApcStore($apc);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsAPC(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['put'])->getMock();
        $apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
        $store = new \Illuminate\Cache\ApcStore($apc);
        $store->forever('foo', 'bar');
    }

    public function testForgetMethodProperlyCallsAPC(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['delete'])->getMock();
        $apc->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $store = new \Illuminate\Cache\ApcStore($apc);
        $store->forget('foo');
    }

    public function testFlushesCached(): void
    {
        $apc = $this->getMockBuilder('Illuminate\Cache\ApcWrapper')->setMethods(['flush'])->getMock();
        $apc->expects($this->once())->method('flush')->willReturn(true);
        $store = new \Illuminate\Cache\ApcStore($apc);
        $result = $store->flush();
        $this->assertTrue($result);
    }
}
