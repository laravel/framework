<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeWhenTest extends AbstractBladeTestCase
{
    public function testIncludeWhensAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', ["foo" => "bar"], array_except(get_defined_vars(), array(\'__data\', \'__path\'))); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\', ["foo" => "bar"])'));
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\'))); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\')'));
    }
}
