<?php

namespace Illuminate\Tests\View\Blade;

class BladeJsonTest extends AbstractBladeTestCase
{
    public function testStatementIsCompiledWithSafeDefaultEncodingOptions()
    {
        $string = 'var foo = @json($var);';
        $expected = 'var foo = <?php echo json_encode($var, 15, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEncodingOptionsCanBeOverwritten()
    {
        $string = 'var foo = @json($var, JSON_HEX_TAG);';
        $expected = 'var foo = <?php echo json_encode($var, JSON_HEX_TAG, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testArgumentCanBeArray()
    {
        $string = 'var foo = @json(["abc", 12.34, [1, 2, false]]);';
        $expected = 'var foo = <?php echo json_encode(["abc", 12.34, [1, 2, false]], 15, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testArgumentCanBeMethodCall()
    {
        $string = 'var foo = @json(Foo::bar("abc", 12.34, [1, 2, false]));';
        $expected = 'var foo = <?php echo json_encode(Foo::bar("abc", 12.34, [1, 2, false]), 15, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testArrayArgumentWithOptions()
    {
        $string = 'var foo = @json(["abc", 12.34], JSON_HEX_TAG, 256);';
        $expected = 'var foo = <?php echo json_encode(["abc", 12.34], JSON_HEX_TAG, 256) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
