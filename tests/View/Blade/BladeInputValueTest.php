<?php

namespace Illuminate\Tests\View\Blade;

class BladeInputValueTest extends AbstractBladeTestCase
{
    public function testValuesAreCompiledWithString()
    {
        $string = "<input @value('test')/>";
        $expected = "<input <?php echo 'value=\"' . 'test' . '\"'; ?>/>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testValuesAreCompiledWithNumber()
    {
        $string = '<input @value(1)/>';
        $expected = "<input <?php echo 'value=\"' . 1 . '\"'; ?>/>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
