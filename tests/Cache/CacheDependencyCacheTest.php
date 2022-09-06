<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\NullStore;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheDependencyCacheTest extends TestCase {
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCacheDependencySetBasicItem()
    {
        $store = new ArrayStore;
        $dependencies = 'dependencies_group';

        $store->dependencies($dependencies)->put('foo', 'bar', 10);

        $this->assertTrue($store->dependencies($dependencies)->exists());
    }

    public function testCacheDependencyInvalidateBasicItem()
    {
        $store = new ArrayStore;
        $dependencies = 'dependencies_group';

        $store->dependencies($dependencies)->put('foo', 'bar', 10);
        $store->dependencies($dependencies)->invalidate();

        $this->assertNull($store->get('foo'));
    }

    public function testFileStoreDependencyInvalidateBasicItem()
    {
        $files = $this->mockFilesystem();

        $store = new FileStore($files, __DIR__);
        $dependencies = 'dependencies_group';

        $store->dependencies($dependencies)->put('foo', 'bar', 10);
        $store->dependencies($dependencies)->invalidate();

        $this->assertNull($store->get('foo'));
    }

    public function testNullStoreDependencyInvalidateBasicItem()
    {
        $store = new NullStore();
        $dependencies = 'dependencies_group';

        $store->dependencies($dependencies)->put('foo', 'bar', 10);
        $store->dependencies($dependencies)->invalidate();

        $this->assertFalse($store->dependencies($dependencies)->exists());
    }

    public function testCacheDependencyInvalidateOnTaggableStore()
    {
        $store = new ArrayStore;
        $dependencies = 'dependencies_group';
        $tags = ['test_tag'];

        $store->tags($tags)->dependencies($dependencies)->put('foo', 'bar', 10);
        $store->dependencies($dependencies)->invalidate();

        $this->assertNull($store->tags($tags)->get('foo'));
    }

    public function testCacheDependencyInvalidateOnTaggableStoreAndCheckIfSameKeyExistsWithoutDependency()
    {
        $store = new ArrayStore;
        $dependencies = 'dependencies_group';
        $tags = ['test_tag'];

        $store->tags($tags)->dependencies($dependencies)->put('foo', 'bar', 10);
        $store->put('foo', 'bar', 10);
        $store->dependencies($dependencies)->invalidate();

        $this->assertNull($store->tags($tags)->get('foo'));
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testCacheDependencyInvalidateOnTaggedAndNotTaggedItem()
    {
        $store = new ArrayStore;
        $dependencies = 'dependencies_group';
        $tags = ['test_tag'];

        $store->tags($tags)->dependencies($dependencies)->put('foo', 'bar', 10);
        $store->dependencies($dependencies)->put('foo2', 'bar', 10);
        $store->dependencies($dependencies)->invalidate();

        $this->assertNull($store->tags($tags)->get('foo'));
        $this->assertNull($store->get('foo2'));
    }

    protected function mockFilesystem()
    {
        return $this->createMock(Filesystem::class);
    }
}
