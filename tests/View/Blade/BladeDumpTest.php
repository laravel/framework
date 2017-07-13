<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeDumpTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDumpStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php dd(\'foo\'); ?>', $compiler->compileString('@dump(\'foo\')'));
        $this->assertEquals('<?php dd($foo); ?>', $compiler->compileString('@dump($foo)'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
