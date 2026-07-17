<?php

namespace Illuminate\Tests\View\Blade;

class BladeUnsetStatementsTest extends AbstractBladeTestCase
{
    public function testUnsetStatementsAreCompiled()
    {
        $string = '@unset ($unset)';
        $expected = '<?php unset($unset); ?>';
        $this->assertSame($expected, $this->compiler->compileString($string));

        $string = '@unset ($unset)))';
        $expected = '<?php unset($unset); ?>))';
        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
