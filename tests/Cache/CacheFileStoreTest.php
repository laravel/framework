<?php

use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class CacheFileStoreTest extends PHPUnit_Framework_TestCase
{
    public function testNullIsReturnedIfFileDoesntExist()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('get')->will($this->throwException(new FileNotFoundException()));
        $store = new FileStore($files, __DIR__);
        $value = $store->get('foo');
        $this->assertNull($value);
    }

    public function testPutCreatesMissingDirectories()
    {
        $files = $this->mockFilesystem();
        $hash = sha1('foo');
        $full_dir = __DIR__.'/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('makeDirectory')->with($this->equalTo($full_dir), $this->equalTo(0777), $this->equalTo(true));
        $files->expects($this->once())->method('put')->with($this->equalTo($full_dir.'/'.$hash));
        $store = new FileStore($files, __DIR__);
        $store->put('foo', '0000000000', 0);
    }

    public function testExpiredItemsReturnNull()
    {
        $files = $this->mockFilesystem();
        $contents = '0000000000';
        $files->expects($this->once())->method('get')->will($this->returnValue($contents));
        $store = $this->getMock('Illuminate\Cache\FileStore', ['forget'], [$files, __DIR__]);
        $store->expects($this->once())->method('forget');
        $value = $store->get('foo');
        $this->assertNull($value);
    }

    public function testValidItemReturnsContents()
    {
        $files = $this->mockFilesystem();
        $contents = '9999999999'.serialize('Hello World');
        $files->expects($this->once())->method('get')->will($this->returnValue($contents));
        $store = new FileStore($files, __DIR__);
        $this->assertEquals('Hello World', $store->get('foo'));
    }

    public function testStoreItemProperlyStoresValues()
    {
        $files = $this->mockFilesystem();
        $store = $this->getMock('Illuminate\Cache\FileStore', ['expiration'], [$files, __DIR__]);
        $store->expects($this->once())->method('expiration')->with($this->equalTo(10))->will($this->returnValue(1111111111));
        $contents = '1111111111'.serialize('Hello World');
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('put')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash), $this->equalTo($contents));
        $store->put('foo', 'Hello World', 10);
    }

    public function testForeversAreStoredWithHighTimestamp()
    {
        $files = $this->mockFilesystem();
        $contents = '9999999999'.serialize('Hello World');
        $hash = sha1('foo');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('put')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash), $this->equalTo($contents));
        $store = new FileStore($files, __DIR__);
        $store->forever('foo', 'Hello World', 10);
    }

    public function testForeversAreNotRemovedOnIncrement()
    {
        $files = $this->mockFilesystem();
        $contents = '9999999999'.serialize('Hello World');
        $store = new FileStore($files, __DIR__);
        $store->forever('foo', 'Hello World');
        $store->increment('foo');
        $files->expects($this->once())->method('get')->will($this->returnValue($contents));
        $this->assertEquals('Hello World', $store->get('foo'));
    }

    public function testRemoveDeletesFileDoesntExist()
    {
        $files = $this->mockFilesystem();
        $hash = sha1('foobull');
        $cache_dir = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        $files->expects($this->once())->method('exists')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash))->will($this->returnValue(false));
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
        $files->expects($this->once())->method('exists')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash))->will($this->returnValue(true));
        $files->expects($this->once())->method('delete')->with($this->equalTo(__DIR__.'/'.$cache_dir.'/'.$hash));
        $store->forget('foobar');
    }

    public function testFlushCleansDirectory()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('isDirectory')->with($this->equalTo(__DIR__))->will($this->returnValue(true));
        $files->expects($this->once())->method('directories')->with($this->equalTo(__DIR__))->will($this->returnValue(['foo']));
        $files->expects($this->once())->method('deleteDirectory')->with($this->equalTo('foo'));

        $store = new FileStore($files, __DIR__);
        $store->flush();
    }

    public function testFlushIgnoreNonExistingDirectory()
    {
        $files = $this->mockFilesystem();
        $files->expects($this->once())->method('isDirectory')->with($this->equalTo(__DIR__.'--wrong'))->will($this->returnValue(false));

        $store = new FileStore($files, __DIR__.'--wrong');
        $store->flush();
    }

    protected function mockFilesystem()
    {
        return $this->getMock('Illuminate\Filesystem\Filesystem');
    }
}
