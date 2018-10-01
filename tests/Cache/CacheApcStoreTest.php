<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ApcStore;
use PHPUnit\Framework\TestCase;
use Illuminate\Cache\ApcWrapper;

class CacheApcStoreTest extends TestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->will($this->returnValue(null));
        $store = new ApcStore($apc, 'foo');
        $this->assertNull($store->get('bar'));
    }

    public function testAPCValueIsReturned()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->will($this->returnValue('bar'));
        $store = new ApcStore($apc);
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testGetMultipleReturnsNullWhenNotFoundAndValueWhenFound()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['get'])->getMock();
        $apc->expects($this->exactly(3))->method('get')->willReturnMap([
            ['foo', 'qux'],
            ['bar', null],
            ['baz', 'norf'],
        ]);
        $store = new ApcStore($apc);
        $this->assertEquals([
            'foo'   => 'qux',
            'bar'   => null,
            'baz'   => 'norf',
        ], $store->many(['foo', 'bar', 'baz']));
    }

    public function testSetMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['put'])->getMock();
        $apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60));
        $store = new ApcStore($apc);
        $store->put('foo', 'bar', 1);
    }

    public function testSetMultipleMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['put'])->getMock();
        $apc->expects($this->exactly(3))->method('put')->withConsecutive([
            $this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60),
        ], [
            $this->equalTo('baz'), $this->equalTo('qux'), $this->equalTo(60),
        ], [
            $this->equalTo('bar'), $this->equalTo('norf'), $this->equalTo(60),
        ]);
        $store = new ApcStore($apc);
        $store->putMany([
            'foo'   => 'bar',
            'baz'   => 'qux',
            'bar'   => 'norf',
        ], 1);
    }

    public function testIncrementMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['increment'])->getMock();
        $apc->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new ApcStore($apc);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['decrement'])->getMock();
        $apc->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new ApcStore($apc);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['put'])->getMock();
        $apc->expects($this->once())->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0));
        $store = new ApcStore($apc);
        $store->forever('foo', 'bar');
    }

    public function testForgetMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['delete'])->getMock();
        $apc->expects($this->once())->method('delete')->with($this->equalTo('foo'));
        $store = new ApcStore($apc);
        $store->forget('foo');
    }

    public function testFlushesCached()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->setMethods(['flush'])->getMock();
        $apc->expects($this->once())->method('flush')->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->flush();
        $this->assertTrue($result);
    }
}
