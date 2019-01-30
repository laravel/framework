<?php

namespace Illuminate\Tests\View\Blade;

class BladeBreakStatementsTest extends AbstractBladeTestCase
{
    public function testBreakStatementsAreCompiled()
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testBreakStatementsWithExpressionAreCompiled()
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(TRUE)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php if(TRUE) break; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testBreakStatementsWithArgumentAreCompiled()
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testBreakStatementsWithSpacedArgumentAreCompiled()
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break( 2 )
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break 2; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testBreakStatementsWithFaultyArgumentAreCompiled()
    {
        $string = '@for ($i = 0; $i < 10; $i++)
test
@break(-2)
@endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?>
test
<?php break 1; ?>
<?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
