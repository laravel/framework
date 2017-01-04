<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeYieldTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testYieldsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->yieldContent(\'foo\'); ?>', $compiler->compileString('@yield(\'foo\')'));
        $this->assertEquals('<?php echo $__env->yieldContent(\'foo\', \'bar\'); ?>', $compiler->compileString('@yield(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->yieldContent(name(foo)); ?>', $compiler->compileString('@yield(name(foo))'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
