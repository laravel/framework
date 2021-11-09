<?php

namespace Illuminate\Tests\View\Blade;

class BladeJsTest extends AbstractBladeTestCase
{
    public function testStatementIsCompiledWithoutAnyOptions()
    {
        $string = '<div x-data="@js($data)"></div>';
        $expected = '<div x-data="<?php echo \Illuminate\Support\Js::from($data)->toHtml() ?>"></div>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonFlagsCanBeSet()
    {
        $string = '<div x-data="@js($data, JSON_FORCE_OBJECT)"></div>';
        $expected = '<div x-data="<?php echo \Illuminate\Support\Js::from($data, JSON_FORCE_OBJECT)->toHtml() ?>"></div>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEncodingDepthCanBeSet()
    {
        $string = '<div x-data="@js($data, JSON_FORCE_OBJECT, 256)"></div>';
        $expected = '<div x-data="<?php echo \Illuminate\Support\Js::from($data, JSON_FORCE_OBJECT, 256)->toHtml() ?>"></div>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
