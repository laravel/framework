<?php

namespace Illuminate\Tests\View\Blade;

class BladeWhenTest extends AbstractBladeTestCase
{
    public function testWhenStatmentsAreCompiledRaw()
    {
        $string = "<span @when(true, 'disabled=\"disabled\"', false)></span>";
        $expected = "<span <?php when(true, 'disabled=\"disabled\"', false); ?>></span>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testWhenStatementsAreCompiledEmpty()
    {
        $string = '<span @when()></span>';
        $expected = "<span <?php when(false,'',false); ?>></span>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
