<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeWhileStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testWhileStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@while ($foo)
test
@endwhile';
        $expected = '<?php while($foo): ?>
test
<?php endwhile; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedWhileStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@while ($foo)
@while ($bar)
test
@endwhile
@endwhile';
        $expected = '<?php while($foo): ?>
<?php while($bar): ?>
test
<?php endwhile; ?>
<?php endwhile; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
