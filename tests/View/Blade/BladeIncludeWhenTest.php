<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIncludeWhenTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIncludeWhensAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', ["foo" => "bar"], array_except(get_defined_vars(), array(\'__data\', \'__path\'))); ?>', $compiler->compileString('@includeWhen(true, \'foo\', ["foo" => "bar"])'));
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\'))); ?>', $compiler->compileString('@includeWhen(true, \'foo\')'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
