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
            ['123'],
            ['1.23'],
            ['\'foo\''],
            ['"foo"'],
            ['<<<TEXT
foo, bar, baz
TEXT'],
            ['<<<\'TEXT\'
foo, bar, baz
TEXT'],
            ['\'foo, bar, baz\''],
            ['"foo, bar, baz"'],
            ['$var->method($foo, $bar, $baz)'],
            ['$var->method([$foo, $bar, $baz])'],
            ['[\'foo\', \'bar\', \'baz\']'],
            ['["foo", "bar", "baz"]'],
            ['[$var->method([$foo, $bar, $baz])]'],
            ['function () { return ["foo", "bar", "baz"]; }'],
            ['fn () => ["foo", "bar", "baz"]'],
            ['[[\'foo\', \'bar\', \'baz\']]'],
            ['[["foo", "bar", "baz"]]'],
            ['$var?->foo?->method($foo, $bar, $baz)'],
            ['new Dummy($foo, $bar, $baz)'],
            ['new class($foo, $bar, $baz) {
    public function __construct(
        public readonly string $foo,
        public readonly string $bar,
        public readonly string $baz,
    )
    {
    }
}'],
        ];
    }
}
