<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeCanEveryStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCanStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@canEvery (\'update\', [$post])
breeze
@elsecanEvery (\'delete\', [$post])
sneeze
@endcanEvery';
        $expected = '<?php if (app(\\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->checkEvery(\'update\', [$post])): ?>
breeze
<?php elseif (app(\\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->checkEvery(\'delete\', [$post])): ?>
sneeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
