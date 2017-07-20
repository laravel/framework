<?php

namespace Illuminate\Tests\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BladeEndSectionsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testEndSectionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php $__env->stopSection(); ?>', $compiler->compileString('@endsection'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
