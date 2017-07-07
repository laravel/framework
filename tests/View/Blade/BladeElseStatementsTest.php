<?php

namespace Illuminate\Tests\View\Blade;

class BladeElseStatementsTest extends AbstractBladeTestCase
{
    public function testElseStatementsAreCompiled()
    {
        $string = '@if (name(foo(bar)))
breeze
@else
boom
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testElseIfStatementsAreCompiled()
    {
        $string = '@if(name(foo(bar)))
breeze
@elseif(boom(breeze))
boom
@endif';
        $expected = '<?php if(name(foo(bar))): ?>
breeze
<?php elseif(boom(breeze)): ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
