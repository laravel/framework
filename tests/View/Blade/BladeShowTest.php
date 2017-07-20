<?php

namespace Illuminate\Tests\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BladeShowTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testShowsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->yieldSection(); ?>', $compiler->compileString('@show'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
