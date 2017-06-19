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
}
