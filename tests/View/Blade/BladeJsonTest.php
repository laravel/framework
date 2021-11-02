<?php

namespace Illuminate\Tests\View\Blade;

class BladeJsonTest extends AbstractBladeTestCase
{
    public function testStatementIsCompiledWithSafeDefaultEncodingOptions()
    {
        $string = 'var foo = @json($var);';
        $expected = 'var foo = <?php echo Illuminate\Support\Json::encode(($var)) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEncodingOptionsCanBeOverwritten()
    {
        $string = 'var foo = @json($var, JSON_HEX_TAG);';
        $expected = 'var foo = <?php echo Illuminate\Support\Json::encode(($var, JSON_HEX_TAG)) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
