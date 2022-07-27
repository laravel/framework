<?php

namespace Illuminate\Tests\View\Blade;

class BladeFilledStatementsTest extends AbstractBladeTestCase
{
    public function testFilledStatementsAreCompiled()
    {
        $string = '@filled (name(foo(bar)))
breeze
@endfilled';
        $expected = '<?php if (! empty((name(foo(bar))))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
