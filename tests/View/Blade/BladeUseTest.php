<?php

namespace Illuminate\Tests\View\Blade;

class BladeUseTest extends AbstractBladeTestCase
{
    public function testUseStatementsAreCompiled()
    {
        $string = "Foo @use('SomeNamespace\SomeClass', 'Foo') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithoutAsAreCompiled()
    {
        $string = "Foo @use('SomeNamespace\SomeClass') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithMultipleValuesAreCompiled()
    {
        $string = "Foo @use(['SomeNamespace\SomeClass', 'AnotherNamespace\AnotherClass']) bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; ?>\n<?php use \AnotherNamespace\AnotherClass; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithMultipleValuesWithAsAreCompiled()
    {
        $string = "Foo @use(['SomeNamespace\SomeClass' => 'Foo', 'AnotherNamespace\AnotherClass' => 'Bar']) bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?>\n<?php use \AnotherNamespace\AnotherClass as Bar; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithMultipleValuesOnNewLinesAreCompiled()
    {
        $string = <<<EOF
            @use([
                'SomeNamespace\SomeClass' => 'Foo',
                'AnotherNamespace\AnotherClass'
            ])
            EOF;

        $expected = "<?php use \SomeNamespace\SomeClass as Foo; ?>\n<?php use \AnotherNamespace\AnotherClass; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
