<?php

namespace Illuminate\Tests\View\Blade;

class BladeYieldTest extends AbstractBladeTestCase
{
    public function testYieldsAreCompiled()
    {
        $this->assertSame('<?php echo $__env->yieldContent(\'foo\'); ?>', $this->compiler->compileString('@yield(\'foo\')'));
        $this->assertSame('<?php echo $__env->yieldContent(\'foo\', \'bar\'); ?>', $this->compiler->compileString('@yield(\'foo\', \'bar\')'));
        $this->assertSame('<?php echo $__env->yieldContent(name(foo)); ?>', $this->compiler->compileString('@yield(name(foo))'));
    }
}
