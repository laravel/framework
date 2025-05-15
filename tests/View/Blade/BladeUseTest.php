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

    public function testUseStatementsWithModifiersAreCompiled()
    {
        $expected = 'Foo <?php use function \SomeNamespace\SomeFunction as Foo; ?> bar';

        $string = "Foo @use('function SomeNamespace\SomeFunction', 'Foo') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = 'Foo @use(function SomeNamespace\SomeFunction, Foo) bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithModifiersWithoutAliasAreCompiled()
    {
        $expected = 'Foo <?php use const \SomeNamespace\SOME_CONST; ?> bar';

        $string = "Foo @use('const SomeNamespace\SOME_CONST') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = 'Foo @use(const SomeNamespace\SOME_CONST) bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithModifiersAndBackslashAtBeginningAreCompiled()
    {
        $expected = 'Foo <?php use function \SomeNamespace\SomeFunction; ?> bar';

        $string = "Foo @use('function \SomeNamespace\SomeFunction') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = 'Foo @use(function \SomeNamespace\SomeFunction) bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithModifiersBackslashAtBeginningAndAliasedAreCompiled()
    {
        $expected = 'Foo <?php use const \SomeNamespace\SOME_CONST as Foo; ?> bar';

        $string = "Foo @use('const \SomeNamespace\SOME_CONST', 'Foo') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = 'Foo @use(const \SomeNamespace\SOME_CONST, Foo) bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithModifiersWithBracesAreCompiledCorrectly()
    {
        $expected = 'Foo <?php use function \SomeNamespace\{Foo, Bar}; ?> bar';

        $string = "Foo @use('function SomeNamespace\{Foo, Bar}') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = 'Foo @use(function SomeNamespace\{Foo, Bar}) bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseFunctionStatementWithBracesAndBackslashAreCompiledCorrectly()
    {
        $expected = 'Foo <?php use const \SomeNamespace\{FOO, BAR}; ?> bar';

        $string = "Foo @use('const \SomeNamespace\{FOO, BAR}') bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = 'Foo @use(const \SomeNamespace\{FOO, BAR}) bar';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
