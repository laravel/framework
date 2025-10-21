<?php

namespace Illuminate\Tests\View\Blade;

class BladeFilledStatementsTest extends AbstractBladeTestCase
{
    public function testFilledStatementsAreCompiled()
    {
        $string = '@filled($var)
            <p>Filled</p>
        @endfilled';
        $expected = '<?php if (filled($var)): ?>
            <p>Filled</p>
        <?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testFilledStatementsWithElseAreCompiled()
    {
        $string = '@filled($var)
            <p>Filled</p>
        @else
            <p>Empty</p>
        @endfilled';
        $expected = '<?php if (filled($var)): ?>
            <p>Filled</p>
        <?php else: ?>
            <p>Empty</p>
        <?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
