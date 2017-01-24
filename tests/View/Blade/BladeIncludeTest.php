<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIncludeTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIncludesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@include(\'foo\')'));
        $this->assertEquals('<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@include(name(foo))'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
