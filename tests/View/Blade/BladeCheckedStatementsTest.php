<?php

namespace Illuminate\Tests\View\Blade;

class BladeCheckedStatementsTest extends AbstractBladeTestCase
{
    public function testCheckedStatementsAreCompiled()
    {
        $string = '<input @checked(name(foo(bar)))/>';
        $expected = "<input <?php if(name(foo(bar))): echo 'checked'; endif; ?>/>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
