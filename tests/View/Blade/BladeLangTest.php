<?php

namespace Illuminate\Tests\View\Blade;

class BladeLangTest extends AbstractBladeTestCase
{
    public function testStatementThatContainsNonConsecutiveParenthesisAreCompiled(): void
    {
        $string = "Foo @lang(function_call('foo(blah)')) bar";
        $expected = "Foo <?php echo app('translator')->getFromJson(function_call('foo(blah)')); ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testLanguageAndChoicesAreCompiled(): void
    {
        $this->assertEquals('<?php echo app(\'translator\')->getFromJson(\'foo\'); ?>', $this->compiler->compileString("@lang('foo')"));
        $this->assertEquals('<?php echo app(\'translator\')->choice(\'foo\', 1); ?>', $this->compiler->compileString("@choice('foo', 1)"));
    }
}
