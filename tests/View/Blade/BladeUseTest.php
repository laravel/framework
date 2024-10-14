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

    public function testUseStatementsWithBackslashAtBeginningAreCompiled()
    {
        $string = "Foo @use('\SomeNamespace\SomeClass') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithBackslashAtBeginningAndAliasedAreCompiled()
    {
        $string = "Foo @use('\SomeNamespace\SomeClass', 'Foo') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
