<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeDdTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDdStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php dd(\'foo\'); ?>', $compiler->compileString('@dd(\'foo\')'));
        $this->assertEquals('<?php dd($foo); ?>', $compiler->compileString('@dd($foo)'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
