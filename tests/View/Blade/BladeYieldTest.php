<?php

namespace Illuminate\Tests\View\Blade;

class BladeYieldTest extends AbstractBladeTestCase
{
    public function testYieldsAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->yieldContent(\'foo\'); ?>', $this->compiler->compileString('@yield(\'foo\')'));
        $this->assertEquals('<?php echo $__env->yieldContent(\'foo\', \'bar\'); ?>', $this->compiler->compileString('@yield(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->yieldContent(name(foo)); ?>', $this->compiler->compileString('@yield(name(foo))'));
    }
}
