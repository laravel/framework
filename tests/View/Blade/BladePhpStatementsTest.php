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

    public function testStringWithParenthesisWithEndPHP()
    {
        $string = "@php(\$data = ['related_to' => 'issue#45388'];) {{ \$data }} @endphp";
        $expected = "<?php(\$data = ['related_to' => 'issue#45388'];) {{ \$data }} ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsWithoutExpressionAreIgnored()
    {
        $string = '@php';
        $expected = '@php';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '{{ "Ignore: @php" }}';
        $expected = '<?php echo e("Ignore: @php"); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsDontParseBladeCode()
    {
        $string = '@php echo "{{ This is a blade tag }}" @endphp';
        $expected = '<?php echo "{{ This is a blade tag }}" ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testVerbatimAndPhpStatementsDontGetMixedUp()
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

    public function testStringWithParenthesisCannotBeCompiled()
    {
        $string = "@php(\$data = ['test' => ')'])";

        $expected = "<?php (\$data = ['test' => ')']); ?>";

        $actual = "<?php (\$data = ['test' => '); ?>'])";

        $this->assertEquals($actual, $this->compiler->compileString($string));
    }

    public function testStringWithEmptyStringDataValue()
    {
        $string = "@php(\$data = ['test' => ''])";

        $expected = "<?php (\$data = ['test' => '']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@php(\$data = ['test' => \"\"])";

        $expected = "<?php (\$data = ['test' => \"\"]); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testStringWithEscapingDataValue()
    {
        $string = "@php(\$data = ['test' => 'won\\'t break'])";

        $expected = "<?php (\$data = ['test' => 'won\\'t break']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@php(\$data = ['test' => \"\\\"escaped\\\"\"])";

        $expected = "<?php (\$data = ['test' => \"\\\"escaped\\\"\"]); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
