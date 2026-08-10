<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TranslationFileLoaderTest extends TestCase
{
    public function testLoadMethodLoadsTranslationsFromAddedPath()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $loader->addPath(__DIR__.'/another');

        $files->expects('exists')->with(__DIR__.'/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/en/messages.php')->andReturn(['foo' => 'bar']);

        $files->expects('exists')->with(__DIR__.'/another/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/another/en/messages.php')->andReturn(['baz' => 'backagesplash']);

        $this->assertEquals(['foo' => 'bar', 'baz' => 'backagesplash'], $loader->load('en', 'messages'));
    }

    public function testLoadMethodHandlesMissingAddedPath()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $loader->addPath(__DIR__.'/missing');

        $files->expects('exists')->with(__DIR__.'/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/en/messages.php')->andReturn(['foo' => 'bar']);

        $files->expects('exists')->with(__DIR__.'/missing/en/messages.php')->andReturn(false);

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', 'messages'));
    }

    public function testLoadMethodOverwritesExistingKeysFromAddedPath()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $loader->addPath(__DIR__.'/another');

        $files->expects('exists')->with(__DIR__.'/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/en/messages.php')->andReturn(['foo' => 'bar']);

        $files->expects('exists')->with(__DIR__.'/another/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/another/en/messages.php')->andReturn(['foo' => 'baz']);

        $this->assertEquals(['foo' => 'baz'], $loader->load('en', 'messages'));
    }

    public function testLoadMethodLoadsTranslationsFromMultipleAddedPaths()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $loader->addPath(__DIR__.'/another');
        $loader->addPath(__DIR__.'/yet-another');

        $files->expects('exists')->with(__DIR__.'/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/en/messages.php')->andReturn(['foo' => 'bar']);

        $files->expects('exists')->with(__DIR__.'/another/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/another/en/messages.php')->andReturn(['baz' => 'backagesplash']);

        $files->expects('exists')->with(__DIR__.'/yet-another/en/messages.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/yet-another/en/messages.php')->andReturn(['qux' => 'quux']);

        $this->assertEquals(['foo' => 'bar', 'baz' => 'backagesplash', 'qux' => 'quux'], $loader->load('en', 'messages'));
    }

    public function testLoadMethodWithoutNamespacesProperlyCallsLoader()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $files->expects('exists')->with(__DIR__.'/en/foo.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/en/foo.php')->andReturn(['messages']);

        $this->assertEquals(['messages'], $loader->load('en', 'foo', null));
    }

    public function testLoadMethodWithoutNamespacesProperlyCallsLoaderWithMultiplePaths()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with(__DIR__.'/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/second/en/foo.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/en/foo.php')->andReturn(['messages' => 'first']);
        $files->expects('getRequire')->with(__DIR__.'/second/en/foo.php')->andReturn(['messages' => 'second']);
        $loader = new FileLoader($files, [__DIR__, __DIR__.'/second']);

        $this->assertEquals(['messages' => 'second'], $loader->load('en', 'foo', null));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoader()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with('bar/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(false);
        $files->expects('getRequire')->with('bar/en/foo.php')->andReturn(['foo' => 'bar']);
        $loader = new FileLoader($files, __DIR__);
        $loader->addNamespace('namespace', 'bar');

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderWithMultiplePaths()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with('test-namespace-dir/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(false);
        $files->expects('exists')->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(false);
        $files->expects('getRequire')->with('test-namespace-dir/en/foo.php')->andReturn(['foo' => 'bar']);
        $loader = new FileLoader($files, [__DIR__, __DIR__.'/second']);
        $loader->addNamespace('namespace', 'test-namespace-dir');

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverrides()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with('bar/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(true);
        $files->expects('getRequire')->with('bar/en/foo.php')->andReturn(['foo' => 'bar']);
        $files->expects('getRequire')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override', 'baz' => 'boom']);
        $loader = new FileLoader($files, __DIR__);
        $loader->addNamespace('namespace', 'bar');

        $this->assertEquals(['foo' => 'override', 'baz' => 'boom'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverridesWithMultiplePaths()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with('test-namespace-dir/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(true);
        $files->expects('getRequire')->with('test-namespace-dir/en/foo.php')->andReturn(['foo' => 'bar']);
        $files->expects('getRequire')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override', 'baz' => 'boom']);
        $files->expects('getRequire')->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override-2', 'baz' => 'boom-2']);
        $loader = new FileLoader($files, [__DIR__, __DIR__.'/second']);
        $loader->addNamespace('namespace', 'test-namespace-dir');

        $this->assertEquals(['foo' => 'override-2', 'baz' => 'boom-2'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverridesWithMultiplePathsWithMissingKey()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with('test-namespace-dir/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(true);
        $files->expects('getRequire')->with('test-namespace-dir/en/foo.php')->andReturn(['foo' => 'bar']);
        $files->expects('getRequire')->with(__DIR__.'/vendor/namespace/en/foo.php')->andReturn(['foo' => 'override', 'baz' => 'boom']);
        $files->expects('getRequire')->with(__DIR__.'/second/vendor/namespace/en/foo.php')->andReturn(['baz' => 'boom-2']);
        $loader = new FileLoader($files, [__DIR__, __DIR__.'/second']);
        $loader->addNamespace('namespace', 'test-namespace-dir');

        $this->assertEquals(['foo' => 'override', 'baz' => 'boom-2'], $loader->load('en', 'foo', 'namespace'));
    }

    public function testEmptyArraysReturnedWhenFilesDontExist()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with(__DIR__.'/en/foo.php')->andReturn(false);
        $files->shouldReceive('getRequire')->never();
        $loader = new FileLoader($files, __DIR__);

        $this->assertSame([], $loader->load('en', 'foo', null));
    }

    public function testEmptyArraysReturnedWhenFilesDontExistForNamespacedItems()
    {
        $files = m::mock(Filesystem::class);
        $files->shouldReceive('getRequire')->never();
        $loader = new FileLoader($files, __DIR__);

        $this->assertSame([], $loader->load('en', 'foo', 'bar'));
    }

    public function testLoadMethodForJSONProperlyCallsLoader()
    {
        $files = m::mock(Filesystem::class);
        $files->expects('exists')->with(__DIR__.'/en.json')->andReturn(true);
        $files->expects('get')->with(__DIR__.'/en.json')->andReturn('{"foo":"bar"}');
        $loader = new FileLoader($files, __DIR__);

        $this->assertEquals(['foo' => 'bar'], $loader->load('en', '*', '*'));
    }

    public function testLoadMethodForJSONProperlyCallsLoaderForMultiplePaths()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $loader->addJsonPath(__DIR__.'/another');

        $files->expects('exists')->with(__DIR__.'/en.json')->andReturn(true);
        $files->expects('exists')->with(__DIR__.'/another/en.json')->andReturn(true);
        $files->expects('get')->with(__DIR__.'/en.json')->andReturn('{"foo":"bar"}');
        $files->expects('get')->with(__DIR__.'/another/en.json')->andReturn('{"foo":"backagebar", "baz": "backagesplash"}');

        $this->assertEquals(['foo' => 'bar', 'baz' => 'backagesplash'], $loader->load('en', '*', '*'));
    }

    public function testLoadMethodThrowExceptionWhenProvideInvalidJSON()
    {
        $files = m::mock(Filesystem::class);
        $loader = new FileLoader($files, __DIR__);
        $loader->addJsonPath(__DIR__.'/invalid');

        $invalidJsonString = '.{"foo":"cricket", "baz": "football"}';
        $files->expects('exists')->with(__DIR__.'/invalid/en.json')->andReturn(true);
        $files->expects('get')->with(__DIR__.'/invalid/en.json')->andReturn($invalidJsonString);

        $this->expectException(\RuntimeException::class);
        $loader->load('en', '*', '*');
    }

    public function testAllRegisteredNamespaceReturnProperly()
    {
        $loader = new FileLoader(m::mock(Filesystem::class), __DIR__);
        $loader->addNamespace('namespace', 'foo');
        $loader->addNamespace('namespace2', 'bar');
        $this->assertEquals(['namespace' => 'foo', 'namespace2' => 'bar'], $loader->namespaces());
    }

    public function testAllAddedJsonPathsReturnProperly()
    {
        $loader = new FileLoader(m::mock(Filesystem::class), __DIR__);
        $path1 = __DIR__.'/another';
        $path2 = __DIR__.'/another2';
        $loader->addJsonPath($path1);
        $loader->addJsonPath($path2);
        $this->assertEquals([$path1, $path2], $loader->jsonPaths());
    }

    public function testAllAddedPathsReturnProperly()
    {
        $loader = new FileLoader(m::mock(Filesystem::class), __DIR__);
        $path1 = __DIR__.'/another';
        $path2 = __DIR__.'/another2';
        $loader->addPath($path1);
        $loader->addPath($path2);
        $this->assertEquals([$path1, $path2], array_slice($loader->paths(), 1));
    }
}
