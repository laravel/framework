<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;
use PHPUnit\Framework\TestCase;

class CacheApcStoreTest extends TestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->willReturn(null);
        $store = new ApcStore($apc, 'foo');
        $this->assertNull($store->get('bar'));
    }

    public function testAPCValueIsReturned()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->willReturn('bar');
        $store = new ApcStore($apc);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testGetMultipleReturnsNullWhenNotFoundAndValueWhenFound()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['get'])->getMock();
        $apc->expects($this->exactly(3))->method('get')->willReturnMap([
            ['foo', 'qux'],
            ['bar', null],
            ['baz', 'norf'],
        ]);
        $store = new ApcStore($apc);
        $this->assertEquals([
            'foo' => 'qux',
            'bar' => null,
            'baz' => 'norf',
        ], $store->many(['foo', 'bar', 'baz']));
    }

    public function testSetMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['put'])->getMock();
        $apc->expects($this->once())
            ->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60))
            ->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->put('foo', 'bar', 60);
        $this->assertTrue($result);
    }

    public function testSetMultipleMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['put'])->getMock();
        $apc->expects($this->exactly(3))->method('put')->withConsecutive([
            $this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(60),
        ], [
            $this->equalTo('baz'), $this->equalTo('qux'), $this->equalTo(60),
        ], [
            $this->equalTo('bar'), $this->equalTo('norf'), $this->equalTo(60),
        ])->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->putMany([
            'foo' => 'bar',
            'baz' => 'qux',
            'bar' => 'norf',
        ], 60);
        $this->assertTrue($result);
    }

    public function testIncrementMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['increment'])->getMock();
        $apc->expects($this->once())->method('increment')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new ApcStore($apc);
        $store->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['decrement'])->getMock();
        $apc->expects($this->once())->method('decrement')->with($this->equalTo('foo'), $this->equalTo(5));
        $store = new ApcStore($apc);
        $store->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['put'])->getMock();
        $apc->expects($this->once())
            ->method('put')->with($this->equalTo('foo'), $this->equalTo('bar'), $this->equalTo(0))
            ->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->forever('foo', 'bar');
        $this->assertTrue($result);
    }

    public function testForgetMethodProperlyCallsAPC()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['delete'])->getMock();
        $apc->expects($this->once())->method('delete')->with($this->equalTo('foo'))->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->forget('foo');
        $this->assertTrue($result);
    }

    public function testFlushesCached()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['flush'])->getMock();
        $apc->expects($this->once())->method('flush')->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->flush();
        $this->assertTrue($result);
    }
}
