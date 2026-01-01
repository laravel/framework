<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CacheApcStoreTest extends TestCase
{
    public static function keyDataProvider(): array
    {
        return [
            'String' => ['foo', 'foo'],
            'Int' => [1, '1'],
            'Backed Enum' => [BackedEnum::Foo, 'foo'],
            'Unit Enum' => [UnitEnum::Foo, 'Foo'],
        ];
    }
    public function testGetReturnsNullWhenNotFound()
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($this->equalTo('foobar'))->willReturn(null);
        $store = new ApcStore($apc, 'foo');
        $this->assertNull($store->get('bar'));
    }

    #[DataProvider('keyDataProvider')]
    public function testAPCValueIsReturned(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($expected)->willReturn($expected);
        $store = new ApcStore($apc);
        $this->assertSame($expected, $store->get($key));
    }
    #[DataProvider('keyDataProvider')]
    public function testAPCFalseValueIsReturned(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['get'])->getMock();
        $apc->expects($this->once())->method('get')->with($expected)->willReturn(false);
        $store = new ApcStore($apc);
        $this->assertFalse($store->get($key));
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

    #[DataProvider('keyDataProvider')]
    public function testSetMethodProperlyCallsAPC(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['put'])->getMock();
        $apc->expects($this->once())
            ->method('put')->with($this->equalTo($expected), $this->equalTo('bar'), $this->equalTo(60))
            ->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->put($key, 'bar', 60);
        $this->assertTrue($result);
    }

    public function testSetMultipleMethodProperlyCallsAPC()
    {
        $apc = Mockery::mock(ApcWrapper::class);

        $apc->shouldReceive('put')
            ->once()
            ->with('foo', 'bar', 60)
            ->andReturn(true);

        $apc->shouldReceive('put')
            ->once()
            ->with('baz', 'qux', 60)
            ->andReturn(true);

        $apc->shouldReceive('put')
            ->once()
            ->with('bar', 'norf', 60)
            ->andReturn(true);

        $store = new ApcStore($apc);
        $result = $store->putMany([
            'foo' => 'bar',
            'baz' => 'qux',
            'bar' => 'norf',
        ], 60);
        $this->assertTrue($result);
    }

    #[DataProvider('keyDataProvider')]
    public function testIncrementMethodProperlyCallsAPC(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['increment'])->getMock();
        $apc->expects($this->once())->method('increment')->with($this->equalTo($expected), $this->equalTo(5));
        $store = new ApcStore($apc);
        $store->increment($key, 5);
    }

    #[DataProvider('keyDataProvider')]
    public function testDecrementMethodProperlyCallsAPC(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['decrement'])->getMock();
        $apc->expects($this->once())->method('decrement')->with($this->equalTo($expected), $this->equalTo(5));
        $store = new ApcStore($apc);
        $store->decrement($key, 5);
    }

    #[DataProvider('keyDataProvider')]
    public function testStoreItemForeverProperlyCallsAPC(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['put'])->getMock();
        $apc->expects($this->once())
            ->method('put')->with($this->equalTo($expected), $this->equalTo('bar'), $this->equalTo(0))
            ->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->forever($key, 'bar');
        $this->assertTrue($result);
    }

    #[DataProvider('keyDataProvider')]
    public function testForgetMethodProperlyCallsAPC(mixed $key, string $expected)
    {
        $apc = $this->getMockBuilder(ApcWrapper::class)->onlyMethods(['delete'])->getMock();
        $apc->expects($this->once())->method('delete')->with($this->equalTo($expected))->willReturn(true);
        $store = new ApcStore($apc);
        $result = $store->forget($key);
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

enum BackedEnum: string
{
    case Foo = 'foo';
}

enum UnitEnum
{
    case Foo;
}
