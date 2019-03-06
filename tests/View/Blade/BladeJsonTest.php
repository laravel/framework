<?php

namespace Illuminate\Tests\View\Blade;

class BladeJsonTest extends AbstractBladeTestCase
{
    public function testBasicStatementIsCompiled()
    {
        $string = 'var foo = @json($var);';
        $expected = 'var foo = <?php echo json_encode($var) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testOptionsArgumentCanBeSpecified()
    {
        $string = 'var foo = @json($var, JSON_HEX_TAG);';
        $expected = 'var foo = <?php echo json_encode($var, JSON_HEX_TAG) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testDepthArgumentCanBeSpecified()
    {
        $string = 'var foo = @json($var, 0, 128);';
        $expected = 'var foo = <?php echo json_encode($var, 0, 128) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testValueArgumentCanContainCommas()
    {
        $string = 'var foo = @json(["value1", "value2", "value3"], JSON_HEX_TAG, 512);';
        $expected = 'var foo = <?php echo json_encode(["value1", "value2", "value3"], JSON_HEX_TAG, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testValueArgumentCanContainFunctions()
    {
        $string = 'var foo = @json(array_merge(["value1", "value2"], ["value3", "value4"]), JSON_HEX_TAG, 512);';
        $expected = 'var foo = <?php echo json_encode(array_merge(["value1", "value2"], ["value3", "value4"]), JSON_HEX_TAG, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
