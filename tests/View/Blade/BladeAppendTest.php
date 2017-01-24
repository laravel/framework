<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeAppendTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testAppendSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->appendSection(); ?>', $compiler->compileString('@append'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
