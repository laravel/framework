<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageAssetLoader;

class FoundationPackageAssetsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testAssetLoading()
    {
        $discovery = new PackageAssetLoader(new Filesystem, __DIR__ . '/fixtures/vendor');

        $this->assertEquals($discovery->get('providers'), ['foo', 'bar', 'baz']);
        $this->assertEquals($discovery->get('facades'), [
            'Foo' => 'FooClass',
            'Bar' => 'BarClass',
        ]);
    }
}
