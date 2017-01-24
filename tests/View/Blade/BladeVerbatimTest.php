<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeVerbatimTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testVerbatimBlocksAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@verbatim {{ $a }} @if($b) {{ $b }} @endif @endverbatim';
        $expected = ' {{ $a }} @if($b) {{ $b }} @endif ';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testVerbatimBlocksWithMultipleLinesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = 'Some text
@verbatim
    {{ $a }}
    @if($b)
        {{ $b }}
    @endif
@endverbatim';
        $expected = 'Some text

    {{ $a }}
    @if($b)
        {{ $b }}
    @endif
';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testMultipleVerbatimBlocksAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@verbatim {{ $a }} @endverbatim {{ $b }} @verbatim {{ $c }} @endverbatim';
        $expected = ' {{ $a }}  <?php echo e($b); ?>  {{ $c }} ';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
