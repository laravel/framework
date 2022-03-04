<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheFileStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
    }

    public function testNullIsReturnedIfFileDoesntExist()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('get')->will($this->throwException(new FileNotFoundException));
        $store = new FileStore($files, __DIR__);
        $value = $store->get('foo');
        $this->assertNull($value);
    }

    public function testUnserializableFileContentGetDeleted()
    {
        $files = $this->mockFilesystem();
        $hash = sha1('foo');
        $cachePath = __DIR__.'/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2).'/'.$hash;

        $files->expects($this->once())->method('get')->willReturn('9999999999-I_am_unserializableee: \(~_~)/');
        $files->expects($this->once())->method('exists')->with($this->equalTo($cachePath))->willReturn(true);
        $files->expects($this->once())->method('delete')->with($this->equalTo($cachePath));

        $value = (new FileStore($files, __DIR__))->get('foo');

        $this->assertNull($value);
    }

    public function testPutCreatesMissingDirectories()
    {
        $files = $this->mockFilesystem();
        $hash = sha1('foo');
        $contents = '0000000000';
        $full_dir = __DIR__.'/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('makeDirectory')->with($this->equalTo($full_dir), $this->equalTo(0777), $this->equalTo(true));
        $files->expects($this->once())->method('put')->with($this->equalTo($full_dir.'/'.$hash))->willReturn(strlen($contents));
        $store = new FileStore($files, __DIR__);
        $result = $store->put('foo', $contents, 0);
        $this->assertTrue($result);
    }

    public function testPutWillConsiderZeroAsEternalTime()
    {
        $files = $this->mockFilesystem();

        $hash = sha1('O--L / key');
        $filePath = __DIR__.'/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2).'/'.$hash;
        $ten9s = '9999999999'; // The "forever" time value.
        $fileContents = $ten9s.serialize('gold');
        $exclusiveLock = true;

        $files->expects($this->once())->method('put')->with(
            $this->equalTo($filePath),
            $this->equalTo($fileContents),
            $this->equalTo($exclusiveLock) // Ensure we do lock the file while putting.
        )->willReturn(strlen($fileContents));

        (new FileStore($files, __DIR__))->put('O--L / key', 'gold', 0);
    }

    public function testPutWillConsiderBigValuesAsEternalTime()
    {
        $files = $this->mockFilesystem();

        $hash = sha1('O--L / key');
        $filePath = __DIR__.'/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2).'/'.$hash;
        $ten9s = '9999999999'; // The "forever" time value.
        $fileContents = $ten9s.serialize('gold');

        $files->expects($this->once())->method('put')->with(
            $this->equalTo($filePath),
            $this->equalTo($fileContents),
        );

        (new FileStore($files, __DIR__))->put('O--L / key', 'gold', (int) $ten9s + 1);
    }

    public function testExpiredItemsReturnNullAndGetDeleted()
    {
        $files = $this->mockFilesystem();
        $contents = '0000000000';
        $files->expects($this->once())->method('get')->willReturn($contents);
        $store = $this->getMockBuilder(FileStore::class)->onlyMethods(['forget'])->setConstructorArgs([$files, __DIR__])->getMock();
        $store->expects($this->once())->method('forget');
        $value = $store->get('foo');
        $this->assertNull($value);
    }

    public function testValidItemReturnsContents()
    {
        $files = $this->mockFilesystem();
        $contents = '9999999999'.serialize('Hello World');
        $files->expects($this->once())->method('get')->willReturn($contents);
        $store = new FileStore($files, __DIR__);
        $this->assertSame('Hello World', $store->get('foo'));
    }

    public function testStoreItemProperlyStoresValues()
    {
        $files = $this->mockFilesystem();
        $store = $this->getMockBuilder(FileStore::class)->onlyMethods(['expiration'])->setConstructorArgs([$files, __DIR__])->getMock();
        $store->expects($this->once())->method('expiration')->with($this->equalTo(10))->willReturn(1111111111);
        $contents = '1111111111'.serialize('Hello World');
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('put')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash), $this->equalTo($contents))->willReturn(strlen($contents));
        $result = $store->put('foo', 'Hello World', 10);
        $this->assertTrue($result);
    }

    public function testStoreItemProperlySetsPermissions()
    {
        $files = m::mock(Filesystem::class);
        $files->shouldIgnoreMissing();
        $store = $this->getMockBuilder(FileStore::class)->onlyMethods(['expiration'])->setConstructorArgs([$files, __DIR__, 0644])->getMock();
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->shouldReceive('put')->withArgs([__DIR__.'/'.$cache_dir.'/'.$hash, m::any(), m::any()])->andReturnUsing(function ($name, $value) {
            return strlen($value);
        });
        $files->shouldReceive('chmod')->withArgs([__DIR__.'/'.$cache_dir.'/'.$hash])->andReturnValues(['0600', '0644'])->times(3);
        $files->shouldReceive('chmod')->withArgs([__DIR__.'/'.$cache_dir.'/'.$hash, 0644])->andReturn([true])->once();
        $result = $store->put('foo', 'foo', 10);
        $this->assertTrue($result);
        $result = $store->put('foo', 'bar', 10);
        $this->assertTrue($result);
        $result = $store->put('foo', 'baz', 10);
        $this->assertTrue($result);
        m::close();
    }

    public function testStoreItemDirectoryProperlySetsPermissions()
    {
        $files = m::mock(Filesystem::class);
        $files->shouldIgnoreMissing();
        $store = $this->getMockBuilder(FileStore::class)->onlyMethods(['expiration'])->setConstructorArgs([$files, __DIR__, 0606])->getMock();
        $hash = sha1('foo');
        $cache_parent_dir = substr($hash, 0, 2);
        $cache_dir = $cache_parent_dir.'/'.substr($hash, 2, 2);

        $files->shouldReceive('put')->withArgs([__DIR__.'/'.$cache_dir.'/'.$hash, m::any(), m::any()])->andReturnUsing(function ($name, $value) {
            return strlen($value);
        });

        $files->shouldReceive('exists')->withArgs([__DIR__.'/'.$cache_dir])->andReturn(false)->once();
        $files->shouldReceive('makeDirectory')->withArgs([__DIR__.'/'.$cache_dir, 0777, true, true])->once();
        $files->shouldReceive('chmod')->withArgs([__DIR__.'/'.$cache_parent_dir])->andReturn(['0600'])->once();
        $files->shouldReceive('chmod')->withArgs([__DIR__.'/'.$cache_parent_dir, 0606])->andReturn([true])->once();
        $files->shouldReceive('chmod')->withArgs([__DIR__.'/'.$cache_dir])->andReturn(['0600'])->once();
        $files->shouldReceive('chmod')->withArgs([__DIR__.'/'.$cache_dir, 0606])->andReturn([true])->once();

        $result = $store->put('foo', 'foo', 10);
        $this->assertTrue($result);
        m::close();
    }

    public function testForeversAreStoredWithHighTimestamp()
    {
        $files = $this->mockFilesystem();
        $contents = '9999999999'.serialize('Hello World');
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('put')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash), $this->equalTo($contents))->willReturn(strlen($contents));
        $store = new FileStore($files, __DIR__);
        $result = $store->forever('foo', 'Hello World', 10);
        $this->assertTrue($result);
    }

    public function testForeversAreNotRemovedOnIncrement()
    {
        $files = $this->mockFilesystem();
        $contents = '9999999999'.serialize('Hello World');
        $store = new FileStore($files, __DIR__);
        $store->forever('foo', 'Hello World');
        $store->increment('foo');
        $files->expects($this->once())->method('get')->willReturn($contents);
        $this->assertSame('Hello World', $store->get('foo'));
    }

    public function testIncrementCanAtomicallyJump()
    {
        $files = $this->mockFilesystem();
        $initialValue = '9999999999'.serialize(1);
        $valueAfterIncrement = '9999999999'.serialize(4);
        $store = new FileStore($files, __DIR__);
        $files->expects($this->once())->method('get')->willReturn($initialValue);
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('put')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash), $this->equalTo($valueAfterIncrement));
        $store->increment('foo', 3);
    }

    public function testIncrementDoesNotExtendCacheLife()
    {
        $files = $this->mockFilesystem();
        $expiration = Carbon::now()->addSeconds(50)->getTimestamp();
        $initialValue = $expiration.serialize(1);
        $valueAfterIncrement = $expiration.serialize(2);
        $store = new FileStore($files, __DIR__);
        $files->expects($this->once())->method('get')->willReturn($initialValue);
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('put')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash), $this->equalTo($valueAfterIncrement));
        $store->increment('foo');
    }

    public function testRemoveDeletesFileDoesntExist()
    {
        $files = $this->mockFilesystem();
        $hash = sha1('foobull');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('exists')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash))->willReturn(false);
        $store = new FileStore($files, __DIR__);
        $store->forget('foobull');
    }

    public function testRemoveDeletesFile()
    {
        $files = $this->mockFilesystem();
        $hash = sha1('foobar');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $store = new FileStore($files, __DIR__);
        $store->put('foobar', 'Hello Baby', 10);
        $files->expects($this->once())->method('exists')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash))->willReturn(true);
        $files->expects($this->once())->method('delete')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash));
        $store->forget('foobar');
    }

    public function testFlushCleansDirectory()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('isDirectory')->with($this->equalTo(__DIR__))->willReturn(true);
        $files->expects($this->once())->method('directories')->with($this->equalTo(__DIR__))->willReturn(['foo']);
        $files->expects($this->once())->method('deleteDirectory')->with($this->equalTo('foo'))->willReturn(true);

        $store = new FileStore($files, __DIR__);
        $result = $store->flush();
        $this->assertTrue($result, 'Flush failed');
    }

    public function testFlushFailsDirectoryClean()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('isDirectory')->with($this->equalTo(__DIR__))->willReturn(true);
        $files->expects($this->once())->method('directories')->with($this->equalTo(__DIR__))->willReturn(['foo']);
        $files->expects($this->once())->method('deleteDirectory')->with($this->equalTo('foo'))->willReturn(false);

        $store = new FileStore($files, __DIR__);
        $result = $store->flush();
        $this->assertFalse($result, 'Flush should not have cleared directories');
    }

    public function testFlushIgnoreNonExistingDirectory()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('isDirectory')->with($this->equalTo(__DIR__.'--wrong'))->willReturn(false);

        $store = new FileStore($files, __DIR__.'--wrong');
        $result = $store->flush();
        $this->assertFalse($result, 'Flush should not clean directory');
    }

    protected function mockFilesystem()
    {
        return $this->createMock(Filesystem::class);
    }
}
