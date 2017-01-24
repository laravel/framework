<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIncludeIfTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIncludeIfsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php if ($__env->exists(\'foo\')) echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@includeIf(\'foo\')'));
        $this->assertEquals('<?php if ($__env->exists(name(foo))) echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $compiler->compileString('@includeIf(name(foo))'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
