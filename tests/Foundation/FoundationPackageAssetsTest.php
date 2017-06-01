<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;

class FoundationPackageAssetsTest extends TestCase
{
    public function testAssetLoading()
    {
        $manifest = new PackageManifest(new Filesystem, __DIR__.'/fixtures', __DIR__.'/fixtures/packages.php');
        $this->assertEquals($manifest->providers(), ['foo', 'bar', 'baz']);
        unlink(__DIR__.'/fixtures/packages.php');
    }
}
