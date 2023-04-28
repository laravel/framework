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

    /**
     * @dataProvider jsonArgumentsProvider
     */
    public function testStatementIsCompiledCorrectlyWhenArgumentContainsComma($expression)
    {
        $string = 'var foo = @json('.$expression.');';
        $expected = 'var foo = <?php echo json_encode('.$expression.', 15, 512) ?>;';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public static function jsonArgumentsProvider()
    {
        return [
            'single quoted string' => ['\'foo, bar, baz\''],
            'double quoted string' => ['"foo, bar, baz"'],
            'method call with variable arguments' => ['$var->method($foo, $bar, $baz)'],
            'method call with array argument' => ['$var->method([$foo, $bar, $baz])'],
            'array single quote string values' => ['[\'foo\', \'bar\', \'baz\']'],
            'array double quote string values' => ['["foo", "bar", "baz"]'],
            'array with method call' => ['[$var->method([$foo, $bar, $baz])]'],
            'closure' => ['function () { return ["foo", "bar", "baz"]; }'],
            'closure short' => ['fn () => ["foo", "bar", "baz"]'],
        ];
    }
}
