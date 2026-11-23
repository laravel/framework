<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FoundationAliasLoaderTest extends TestCase
{
    public function setUp(): void
    {
        AliasLoader::setInstance(null);
        AliasLoader::setFacadeNamespace('Facades\\');
    }

    public function testLoaderCanBeCreatedAndRegisteredOnce()
    {
        $loader = AliasLoader::getInstance(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $loader->getAliases());
        $this->assertFalse($loader->isRegistered());
        $loader->register();

        $this->assertTrue($loader->isRegistered());
    }

    public function testGetInstanceCreatesOneInstance()
    {
        $loader = AliasLoader::getInstance(['foo' => 'bar']);
        $this->assertSame($loader, AliasLoader::getInstance());
    }

    public function testLoaderCanBeCreatedAndRegisteredMergingAliases()
    {
        $loader = AliasLoader::getInstance(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $loader->getAliases());

        $loader = AliasLoader::getInstance(['foo2' => 'bar2']);
        $this->assertEquals(['foo2' => 'bar2', 'foo' => 'bar'], $loader->getAliases());

        // override keys
        $loader = AliasLoader::getInstance(['foo' => 'baz']);
        $this->assertEquals(['foo2' => 'bar2', 'foo' => 'baz'], $loader->getAliases());
    }

    public function testLoaderCanAliasAndLoadClasses()
    {
        $loader = AliasLoader::getInstance(['some_alias_foo_bar' => FoundationAliasLoaderStub::class]);

        $result = $loader->load('some_alias_foo_bar');

        $this->assertInstanceOf(FoundationAliasLoaderStub::class, new \some_alias_foo_bar);
        $this->assertTrue($result);

        $result2 = $loader->load('bar');
        $this->assertNull($result2);
    }

    public function testSetAlias()
    {
        $loader = AliasLoader::getInstance();
        $loader->setAliases(['some_alias_foo' => FoundationAliasLoaderStub::class]);

        $result = $loader->load('some_alias_foo');

        $fooObj = new \some_alias_foo;
        $this->assertInstanceOf(FoundationAliasLoaderStub::class, $fooObj);
        $this->assertTrue($result);
    }

    public function testRealTimeFacadeStubIsKeptWhenAnotherProcessHoldsItOpen()
    {
        $storage = $this->makeStorageDirectory();

        try {
            $loader = FoundationAliasLoaderConcurrentWriterStub::getInstance();

            $path = $loader->ensureStubExists('Facades\Some\RealTimeFacade');

            $this->assertSame($loader->stubPath, $path);
            $this->assertStringContainsString('class RealTimeFacade', file_get_contents($path));
            $this->assertSame([basename($path)], array_map('basename', glob($storage.'/framework/cache/*')));
        } finally {
            if (is_resource($loader->handle)) {
                fclose($loader->handle);
            }

            $this->removeStorageDirectory($storage);
        }
    }

    public function testRealTimeFacadeStubFailsWhenNoStubExistsAfterTheRenameFails()
    {
        $storage = $this->makeStorageDirectory();

        try {
            $loader = FoundationAliasLoaderConcurrentWriterStub::getInstance();
            $loader->blockWithDirectory = true;

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to write the real-time facade stub');

            $loader->ensureStubExists('Facades\Some\RealTimeFacade');
        } finally {
            $this->assertSame([basename($loader->stubPath)], array_map('basename', glob($storage.'/framework/cache/*')));

            $this->removeStorageDirectory($storage);
        }
    }

    protected function makeStorageDirectory()
    {
        $storage = sys_get_temp_dir().'/'.uniqid('alias_loader_', true);

        mkdir($storage.'/framework/cache', 0777, true);

        $app = new Application($storage);
        $app->useStoragePath($storage);

        return $storage;
    }

    protected function removeStorageDirectory($storage)
    {
        Container::setInstance(null);

        (new Filesystem)->deleteDirectory($storage);
    }
}

class FoundationAliasLoaderStub
{
    //
}

class FoundationAliasLoaderConcurrentWriterStub extends AliasLoader
{
    public $stubPath;

    public $handle;

    public $blockWithDirectory = false;

    public function ensureStubExists($alias)
    {
        return $this->ensureFacadeExists($alias);
    }

    /**
     * Stand in for another process that, after this one has checked that the stub is missing
     * and before it renames its own over it, creates the stub and keeps it open, or leaves
     * something at the stub's path that a rename cannot replace.
     */
    protected function formatFacadeStub($alias, $stub)
    {
        $stub = parent::formatFacadeStub($alias, $stub);

        $this->stubPath = storage_path('framework/cache/facade-'.sha1($alias).'.php');

        if ($this->blockWithDirectory) {
            mkdir($this->stubPath);

            return $stub;
        }

        file_put_contents($this->stubPath, $stub);

        $this->handle = fopen($this->stubPath, 'r');

        return $stub;
    }
}
