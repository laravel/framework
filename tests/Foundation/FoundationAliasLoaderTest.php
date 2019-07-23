<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\AliasLoader;

class FoundationAliasLoaderTest extends TestCase
{
    public function testLoaderCanBeCreatedAndRegisteredOnce()
    {
        $loader = AliasLoader::getInstance(['foo' => 'bar']);

        $this->assertSame(array_intersect(['foo' => 'bar'], $loader->getAliases()), ['foo' => 'bar']);
    }

    public function testGetInstanceCreatesOneInstance()
    {
        $loader = AliasLoader::getInstance(['foo' => 'bar']);
        $this->assertSame($loader, AliasLoader::getInstance());
    }

    public function testLoaderCanBeCreatedAndRegisteredMergingAliases()
    {
        $loader = AliasLoader::getInstance(['foo' => 'bar']);

        $this->assertSame(array_intersect(['foo' => 'bar'], $loader->getAliases()), ['foo' => 'bar']);

        $loader = AliasLoader::getInstance(['foo2' => 'bar2']);
        $this->assertSame(array_intersect(['foo2' => 'bar2', 'foo' => 'bar'], $loader->getAliases()), ['foo2' => 'bar2', 'foo' => 'bar']);

        // override keys
        $loader = AliasLoader::getInstance(['foo' => 'baz']);
        $this->assertSame(array_intersect(['foo2' => 'bar2', 'foo' => 'baz'], $loader->getAliases()), ['foo2' => 'bar2', 'foo' => 'baz']);
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
}

class FoundationAliasLoaderStub
{
    //
}
