<?php

namespace Illuminate\Tests\View\Blade;

class BladePhpStatementsTest extends AbstractBladeTestCase
{
    public function testPhpStatementsWithExpressionAreCompiled()
    {
        $string = '@php($set = true)';
        $expected = '<?php ($set = true); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsWithoutExpressionAreCompiled()
    {
        $string = '@php';
        $expected = '<?php ';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
