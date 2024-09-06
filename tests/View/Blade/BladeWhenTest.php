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

    public function testWhenStatementCompiledEmpty()
    {
        $string = '<span @when()></span>';
        $expected = "<span <?php when(); ?>></span>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testWhenStatementsArePassedTwoArgumentsItDefaultsToEscaped()
    {
        $string = "<span @when(true, 'hello')></span>";
        $expected = "<span <?php when(true, 'hello'); ?>></span>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
