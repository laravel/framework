<?php

namespace Illuminate\Tests\View\Blade;

class BladeExtendsTest extends AbstractBladeTestCase
{
    public function testExtendsAreCompiled()
    {
        $string = "@extends('foo')\ntest";
        $expected = "test\n".'<?php echo $__env->make(\'foo\', array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@extends(name(foo))'."\n".'test';
        $expected = "test\n".'<?php echo $__env->make(name(foo), array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testSequentialCompileStringCalls()
    {
        $string = "@extends('foo')\ntest";
        $expected = "test\n".'<?php echo $__env->make(\'foo\', array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // use the same compiler instance to compile another template with @extends directive
        $string = "@extends(name(foo))\ntest";
        $expected = "test\n".'<?php echo $__env->make(name(foo), array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testExtendsFirstAreCompiled()
    {
        $string = "@extendsFirst(['foo', 'milwad'])\ntest";
        $expected = "test\n".'<?php echo $__env->first([\'foo\', \'milwad\'], array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@extendsFirst([name(foo), name(milwad)])'."\n".'test';
        $expected = "test\n".'<?php echo $__env->first([name(foo), name(milwad)], array_diff_key(get_defined_vars(), [\'__data\' => 1, \'__path\' => 1]))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
