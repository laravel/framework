<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeEscapedTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testEscapedWithAtDirectivesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('@foreach', $compiler->compileString('@@foreach'));
        $this->assertEquals('@verbatim @continue @endverbatim', $compiler->compileString('@@verbatim @@continue @@endverbatim'));
        $this->assertEquals('@foreach($i as $x)', $compiler->compileString('@@foreach($i as $x)'));
        $this->assertEquals('@continue @break', $compiler->compileString('@@continue @@break'));
        $this->assertEquals('@foreach(
            $i as $x
        )', $compiler->compileString('@@foreach(
            $i as $x
        )'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
