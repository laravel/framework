<?php

namespace Illuminate\Tests\View\Blade;

class BladeTransTest extends AbstractBladeTestCase
{
    public function testStatementThatContainsNonConsecutiveParenthesisAreCompiled()
    {
        $string = "Foo @trans(function_call('foo(blah)')) bar";
        $expected = "Foo <?php echo app('translator')->get(function_call('foo(blah)')); ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testTranslationIsCompiled()
    {
        $this->assertSame('<?php echo app(\'translator\')->get(\'foo\'); ?>', $this->compiler->compileString("@trans('foo')"));
    }
}
