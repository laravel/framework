<?php

namespace Illuminate\Tests\View\Blade;

class BladeEachTest extends AbstractBladeTestCase
{
    public function testShowEachAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderEach(\'foo\', \'bar\'); ?>', $this->compiler->compileString('@each(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->renderEach(name(foo)); ?>', $this->compiler->compileString('@each(name(foo))'));
    }
}
