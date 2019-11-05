<?php

namespace Illuminate\Tests\View\Blade;

class BladeLangTest extends AbstractBladeTestCase
{
    public function testStatementThatContainsNonConsecutiveParenthesisAreCompiled()
    {
        $string = "Foo @lang(function_call('foo(blah)')) bar";
        $expected = "Foo <?php echo app('translator')->get(function_call('foo(blah)')); ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testLanguageAndChoicesAreCompiled()
    {
        $this->assertSame('<?php echo app(\'translator\')->get(\'foo\'); ?>', $this->compiler->compileString("@lang('foo')"));
        $this->assertSame('<?php echo app(\'translator\')->choice(\'foo\', 1); ?>', $this->compiler->compileString("@choice('foo', 1)"));
    }
}
