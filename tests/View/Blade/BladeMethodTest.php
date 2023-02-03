<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Support\Str;

class BladeMethodTest extends AbstractBladeTestCase
{
    public function testPatch()
    {
        $string = '@patch()';
        $expected = "<?php echo method_field('PATCH'); ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPut()
    {
        $string = '@put()';
        $expected = "<?php echo method_field('PUT'); ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testDelete()
    {
        $string = '@delete()';
        $expected = "<?php echo method_field('DELETE'); ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPatchWithUrl()
    {
        $string = '@patch("/foo")';
        $expected = 'method="POST" action="<?php echo Illuminate\Support\Str::of("/foo")->whenContains("?", fn ($url) => $url->append("&_method=PATCH"), fn ($url) => $url->append("?_method=PATCH"))->toString(); ?>"';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPutWithUrl()
    {
        $string = '@put("/foo")';
        $expected = 'method="POST" action="<?php echo Illuminate\Support\Str::of("/foo")->whenContains("?", fn ($url) => $url->append("&_method=PUT"), fn ($url) => $url->append("?_method=PUT"))->toString(); ?>"';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testDeleteWithUrl()
    {
        $string = '@delete("/foo")';
        $expected = 'method="POST" action="<?php echo Illuminate\Support\Str::of("/foo")->whenContains("?", fn ($url) => $url->append("&_method=DELETE"), fn ($url) => $url->append("?_method=DELETE"))->toString(); ?>"';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}

