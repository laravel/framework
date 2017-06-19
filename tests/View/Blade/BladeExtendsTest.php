<?php

namespace Illuminate\Tests\View\Blade;

class BladeExtendsTest extends AbstractBladeTestCase
{
    public function testExtendsAreCompiled()
    {
        $string = '@extends(\'foo\')
test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@extends(name(foo))'.PHP_EOL.'test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testSequentialCompileStringCalls()
    {
        $string = '@extends(\'foo\')
test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        // use the same compiler instance to compile another template with @extends directive
        $string = '@extends(name(foo))'.PHP_EOL.'test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
