<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;

class FoundationPackageManifestTest extends TestCase
{
    public function testAssetLoading()
    {
        @unlink(__DIR__.'/fixtures/packages.php');
        $manifest = new PackageManifest(new Filesystem, __DIR__.'/fixtures', __DIR__.'/fixtures/packages.php');
        $this->assertEquals(['foo', 'bar', 'baz'], $manifest->providers());
        $this->assertEquals(['Foo' => 'Foo\\Facade'], $manifest->aliases());
        unlink(__DIR__.'/fixtures/packages.php');
    }
}
