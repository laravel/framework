<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeCanStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCanStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@can (\'update\', [$post])
breeze
@elsecan(\'delete\', [$post])
sneeze
@endcan';
        $expected = '<?php if (app(\\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check(\'update\', [$post])): ?>
breeze
<?php elseif (app(\\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check(\'delete\', [$post])): ?>
sneeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
