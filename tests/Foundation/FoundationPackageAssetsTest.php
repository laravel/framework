<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageAssetLoader;

class FoundationPackageAssetsTest extends TestCase
{
    public function testAssetLoading()
    {
        $assetLoader = new PackageAssetLoader(new Filesystem, __DIR__.'/fixtures/vendor');

        $this->assertEquals($assetLoader->get('providers'), ['foo', 'bar', 'baz']);
    }
}
