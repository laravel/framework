<?php

namespace Illuminate\Tests\Blade;

use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BladeStackTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testStackIsCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@stack(\'foo\')';
        $expected = '<?php echo $__env->yieldPushContent(\'foo\'); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
