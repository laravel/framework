<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeWhenTest extends AbstractBladeTestCase
{
    public function testIncludeWhensAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', ["foo" => "bar"], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\', ["foo" => "bar"])'));
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\')'));
    }
}
