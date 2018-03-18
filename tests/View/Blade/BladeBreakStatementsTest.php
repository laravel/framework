<?php

namespace Illuminate\Tests\View\Blade;

class BladeBreakStatementsTest extends AbstractBladeTestCase
{
    public function testBreakStatementsAreCompiled(): void
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

    public function testBreakStatementsWithExpressionAreCompiled(): void
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

    public function testBreakStatementsWithArgumentAreCompiled(): void
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

    public function testBreakStatementsWithSpacedArgumentAreCompiled(): void
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

    public function testBreakStatementsWithFaultyArgumentAreCompiled(): void
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
