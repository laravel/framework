<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeBreakStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBreakStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testBreakStatementsWithExpressionAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(TRUE)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php if(TRUE) break; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testBreakStatementsWithArgumentAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testBreakStatementsWithSpacedArgumentAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break( 2 )
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testBreakStatementsWithFaultyArgumentAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(-2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break 1; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
