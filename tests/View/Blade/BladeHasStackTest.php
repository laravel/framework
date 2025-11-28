<?php

namespace Illuminate\Tests\View\Blade;

class BladeHasStackTest extends AbstractBladeTestCase
{
    public function testHasStackStatementsAreCompiled()
    {
        $string = '@hasStack("stack")
breeze
@endif';
        $expected = '<?php if (! $__env->isStackEmpty("stack")): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
