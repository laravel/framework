<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeEachTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testShowEachAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->renderEach(\'foo\', \'bar\'); ?>', $compiler->compileString('@each(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->renderEach(name(foo)); ?>', $compiler->compileString('@each(name(foo))'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
