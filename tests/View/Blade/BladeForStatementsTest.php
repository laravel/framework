<?php

namespace Illuminate\Tests\View\Blade;

class BladeForStatementsTest extends AbstractBladeTestCase
{
    public function testForStatementsAreCompiled()
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testNestedForStatementsAreCompiled()
    {
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
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
