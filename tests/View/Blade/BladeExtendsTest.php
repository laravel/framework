<?php

namespace Illuminate\Tests\View\Blade;

class BladeExtendsTest extends AbstractBladeTestCase
{
    public function testExtendsAreCompiled()
    {
        $string = '@extends(\'foo\')'."\n".'test';
        $expected = "test\n".'<?php echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@extends(name(foo))'."\n".'test';
        $expected = "test\n".'<?php echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testSequentialCompileStringCalls()
    {
        $string = '@extends(\'foo\')'."\n".'test';
        $expected = "test\n".'<?php echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // use the same compiler instance to compile another template with @extends directive
        $string = '@extends(name(foo))'."\n".'test';
        $expected = "test\n".'<?php echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testExtendsFirstAreCompiled()
    {
        $string = '@extendsFirst([\'foo\', \'milwad\'])'."\n".'test';
        $expected = "test\n".'<?php echo $__env->first([\'foo\', \'milwad\'], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@extendsFirst([name(foo), name(milwad)])'."\n".'test';
        $expected = "test\n".'<?php echo $__env->first([name(foo), name(milwad)], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
