<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use PHPUnit\Framework\TestCase;

class FoundationPackageManifestTest extends TestCase
{
    public function testAssetLoading()
    {
        @unlink(__DIR__.'/fixtures/packages.php');
        $manifest = new PackageManifest(new Filesystem, __DIR__.'/fixtures', __DIR__.'/fixtures/packages.php');
        $this->assertEquals(['foo', 'bar', 'baz', 'bat'], $manifest->providers());
        $this->assertEquals(['Foo' => 'Foo\\Facade', 'Bat' => 'Bat\\Facade'], $manifest->aliases());
        unlink(__DIR__.'/fixtures/packages.php');
    }
}
