<?php

use Illuminate\Foundation\AliasLoader;

class FoundationAliasLoaderTest extends PHPUnit_Framework_TestCase
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
}
