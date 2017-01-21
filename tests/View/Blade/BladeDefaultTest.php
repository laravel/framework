<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeDefaultTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDefaultStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php if (isset($var) && (string) ($var) != \'\'): echo e($var); else: ?>', $compiler->compileString('@default($var)'));
    }

    public function testEndDefaultStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php endif; ?>', $compiler->compileString('@enddefault'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
