<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@if (name(foo(bar)))
breeze
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testSwitchstatementsAreCompiled()
    {
        $string = '@switch(true)
@case(1)
foo

@case(2)
bar
@endswitch

foo

@switch(true)
@case(1)
foo

@case(2)
bar
@endswitch';
        $expected = '<?php switch(true):
case (1): ?>
foo

<?php case (2): ?>
bar
<?php endswitch; ?>

foo

<?php switch(true):
case (1): ?>
foo

<?php case (2): ?>
bar
<?php endswitch; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
