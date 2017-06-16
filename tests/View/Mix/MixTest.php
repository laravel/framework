<?php

namespace Illuminate\Tests\View\Mix;

use Illuminate\Support\HtmlString;
use Illuminate\View\Mix\MixException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MixTest extends TestCase
{
    public function setUp()
    {
        app()->singleton('path.public', function () {
            return __DIR__ . '/stubs/';
        });
    }

    public function tearDown()
    {
        m::close();
    }

    public function testMixMethodWhenDisabled()
    {
        $this->assertEquals(new HTMLString('Mix is disabled!'), $this->getMix()->disable()->mix('foo'));
    }

    private function getMix()
    {
        return new \Illuminate\View\Mix\Mix();
    }

    public function testMixMethodThrowAnExceptionIfMixManifestDoesNotExist()
    {
        $this->expectException(MixException::class);
        $this->expectExceptionMessage('The Mix manifest does not exist.');
        $this->getMix()->mix('foo');
    }

    public function testMixMethodThrowAnExceptionIfPathDoesNotExistInManifest()
    {
        $this->expectException(MixException::class);
        $this->expectExceptionMessage('Unable to locate Mix file: /baz. Please check your webpack.mix.js output paths and try again.');

        $this->getMix()->mix('baz', 'compiled');
    }

    public function testMixMethodWhenCompiled()
    {
        $this->assertEquals(new HTMLString('/compiled/bar'), $this->getMix()->mix('foo', 'compiled'));
    }

    public function testMixMethodWhenHMR()
    {
        $this->assertEquals(new HTMLString('//localhost:8080/foo'), $this->getMix()->mix('foo', 'hmr'));
    }
}
