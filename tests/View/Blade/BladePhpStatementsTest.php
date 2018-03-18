<?php

namespace Illuminate\Tests\View\Blade;

class BladePhpStatementsTest extends AbstractBladeTestCase
{
    public function testPhpStatementsWithExpressionAreCompiled(): void
    {
        $string = '@php($set = true)';
        $expected = '<?php ($set = true); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsWithoutExpressionAreIgnored(): void
    {
        $string = '@php';
        $expected = '@php';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '{{ "Ignore: @php" }}';
        $expected = '<?php echo e("Ignore: @php"); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsDontParseBladeCode(): void
    {
        $string = '@php echo "{{ This is a blade tag }}" @endphp';
        $expected = '<?php echo "{{ This is a blade tag }}" ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testVerbatimAndPhpStatementsDontGetMixedUp(): void
    {
        $string = "@verbatim {{ Hello, I'm not blade! }}"
                ."\n@php echo 'And I'm not PHP!' @endphp"
                ."\n@endverbatim {{ 'I am Blade' }}"
                ."\n@php echo 'I am PHP {{ not Blade }}' @endphp";

        $expected = " {{ Hello, I'm not blade! }}"
                ."\n@php echo 'And I'm not PHP!' @endphp"
                ."\n <?php echo e('I am Blade'); ?>"
                ."\n\n<?php echo 'I am PHP {{ not Blade }}' ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
