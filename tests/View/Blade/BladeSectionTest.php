<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeSectionTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSectionStartsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->startSection(\'foo\'); ?>', $compiler->compileString('@section(\'foo\')'));
        $this->assertEquals('<?php $__env->startSection(name(foo)); ?>', $compiler->compileString('@section(name(foo))'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
