<?php

namespace Illuminate\Tests\View\Blade;

class BladeJsonTest extends AbstractBladeTestCase
{
    public function testStatementIsCompiledWithSafeDefaultEncodingOptions()
    {
        $string = '@json($var);';
        $expected = '<?php echo json_encode($var, 15, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testOptionsArgumentCanBeSpecified()
    {
        $string = '@json($var, JSON_HEX_TAG);';
        $expected = '<?php echo json_encode($var, JSON_HEX_TAG, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testDepthArgumentCanBeSpecified()
    {
        $string = '@json($var, 0, 128);';
        $expected = '<?php echo json_encode($var, 0, 128) ?>;';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testValueArgumentCanContainCommas()
    {
        $string = '@json(["value1", "value2", "value3"], JSON_HEX_TAG, 512);';
        $expected = '<?php echo json_encode(["value1", "value2", "value3"], JSON_HEX_TAG, 512) ?>;';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testValueArgumentCanContainFunctions()
    {
        $string = '@json(array_merge(["value1", "value2"], ["value3", "value4"]), JSON_HEX_TAG, 512);';
        $expected = '<?php echo json_encode(array_merge(["value1", "value2"], ["value3", "value4"]), JSON_HEX_TAG, 512) ?>;';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
