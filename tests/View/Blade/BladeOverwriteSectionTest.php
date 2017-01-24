<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeOverwriteSectionTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testOverwriteSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->stopSection(true); ?>', $compiler->compileString('@overwrite'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
