<?php

namespace Illuminate\Tests\View\Blade;

class BladeUseTest extends AbstractBladeTestCase
{
    public function testUseStatementsAreCompiled()
    {
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?> bar";

        $string = "Foo @use('SomeNamespace\SomeClass', 'Foo') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "Foo @use(SomeNamespace\SomeClass, Foo) bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithoutAsAreCompiled()
    {
        $expected = "Foo <?php use \SomeNamespace\SomeClass; ?> bar";

        $string = "Foo @use('SomeNamespace\SomeClass') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "Foo @use(SomeNamespace\SomeClass) bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithBackslashAtBeginningAreCompiled()
    {
        $expected = "Foo <?php use \SomeNamespace\SomeClass; ?> bar";

        $string = "Foo @use('\SomeNamespace\SomeClass') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "Foo @use(\SomeNamespace\SomeClass) bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithBackslashAtBeginningAndAliasedAreCompiled()
    {
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?> bar";

        $string = "Foo @use('\SomeNamespace\SomeClass', 'Foo') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "Foo @use(\SomeNamespace\SomeClass, Foo) bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithBracesAreCompiledCorrectly()
    {
        $expected = "Foo <?php use \SomeNamespace\{Foo, Bar}; ?> bar";

        $string = "Foo @use('SomeNamespace\{Foo, Bar}') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "Foo @use(SomeNamespace\{Foo, Bar}) bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementWithBracesAndBackslashAreCompiledCorrectly()
    {
        $expected = "Foo <?php use \SomeNamespace\{Foo, Bar}; ?> bar";

        $string = "Foo @use('\SomeNamespace\{Foo, Bar}') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "Foo @use(\SomeNamespace\{Foo, Bar}) bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
