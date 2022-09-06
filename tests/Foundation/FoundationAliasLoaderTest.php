<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\AliasLoader;
use PHPUnit\Framework\TestCase;

class FoundationAliasLoaderTest extends TestCase
{
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

    public function testSetAliases()
    {
        $loader = AliasLoader::getInstance();
        $aliases = $loader->getAliases();
        $this->assertEmpty($aliases);

        $loader->setAliases(['foo' => 'bar']);
        $aliases = $loader->getAliases();
        $this->assertEquals(['foo' => 'bar'], $aliases);
        $this->assertCount(1, $aliases);

        //test set more than one alias
        $loader->setAliases(['foo' => 'bar', 'bar' => 'baz']);
        $aliases = $loader->getAliases();
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $aliases);
        $this->assertCount(2, $aliases);

        //test override last alias
        $loader->setAliases(['bar' => 'baz']);
        $loader->setAliases(['foo' => 'bar']);
        $aliases = $loader->getAliases();
        $this->assertEquals(['foo' => 'bar'], $aliases);
        $this->assertCount(1, $aliases);

        //get instance merges aliases
        $loader::getInstance(['foo2', 'bar2']);
        $aliases = $loader->getAliases();
        $this->assertEquals(['foo' => 'bar', 'foo2', 'bar2'], $aliases);
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
