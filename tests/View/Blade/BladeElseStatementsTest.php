<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeElseStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testElseStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@if (name(foo(bar)))
breeze
@else
boom
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testElseIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@if(name(foo(bar)))
breeze
@elseif(boom(breeze))
boom
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php elseif(boom(breeze)): ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
