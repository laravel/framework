<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\AliasLoader;

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
}
