<?php

namespace Illuminate\Tests\View\Blade;

class BladeExtendsTest extends AbstractBladeTestCase
{
    public function testExtendsAreCompiled()
    {
        $string = '@extends(\'foo\')
test';
        $expected = "test\n".'<?php echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@extends(name(foo))'."\n".'test';
        $expected = "test\n".'<?php echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testSequentialCompileStringCalls()
    {
        $string = '@extends(\'foo\')
test';
        $expected = "test\n".'<?php echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // use the same compiler instance to compile another template with @extends directive
        $string = "@extends(name(foo))\ntest";
        $expected = "test\n".'<?php echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
