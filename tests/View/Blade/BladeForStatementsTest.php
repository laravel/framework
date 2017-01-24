<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeForStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testForStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
test
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedForStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@for ($i = 0; $i < 10; $i++)
@for ($j = 0; $j < 20; $j++)
test
@endfor
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
<?php for($j = 0; $j < 20; $j++): ?>
test
<?php endfor; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
