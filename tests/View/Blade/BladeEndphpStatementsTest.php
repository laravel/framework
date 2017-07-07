<?php

namespace Illuminate\Tests\View\Blade;

class BladeEndphpStatementsTest extends AbstractBladeTestCase
{
    public function testEndphpStatementsAreCompiled()
    {
        $string = '@endphp';
        $expected = ' ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
