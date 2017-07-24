<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeContinueStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testContinueStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testContinueStatementsWithExpressionAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(TRUE)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php if(TRUE) continue; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testContinueStatementsWithArgumentAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testContinueStatementsWithSpacedArgumentAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue( 2 )
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testContinueStatementsWithFaultyArgumentAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@continue(-2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php continue 1; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
