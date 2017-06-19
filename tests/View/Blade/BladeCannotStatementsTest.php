<?php

namespace Illuminate\Tests\View\Blade;

class BladeCannotStatementsTest extends AbstractBladeTestCase
{
    public function testCannotStatementsAreCompiled()
    {
        $string = '@cannot (\'update\', [$post])
breeze
@elsecannot(\'delete\', [$post])
sneeze
@endcannot';
        $expected = '<?php if (app(\\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies(\'update\', [$post])): ?>
breeze
<?php elseif (app(\\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies(\'delete\', [$post])): ?>
sneeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
