<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeExtendsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testExtendsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@extends(\'foo\')
test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));

        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@extends(name(foo))'.PHP_EOL.'test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testSequentialCompileStringCalls()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@extends(\'foo\')
test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));

        // use the same compiler instance to compile another template with @extends directive
        $string = '@extends(name(foo))'.PHP_EOL.'test';
        $expected = 'test'.PHP_EOL.'<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
