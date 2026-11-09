<?php

namespace Illuminate\Tests\View\Blade;

class BladeStackTest extends AbstractBladeTestCase
{
    public function testStackIsCompiled()
    {
        $string = '@stack(\'foo\')';
        $expected = '<?php echo $__env->yieldStackPlaceholder(\'foo\'); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '@stack(\'foo))\')';
        $expected = '<?php echo $__env->yieldStackPlaceholder(\'foo))\'); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
