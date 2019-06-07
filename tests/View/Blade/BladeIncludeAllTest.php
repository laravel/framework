<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeAllTest extends AbstractBladeTestCase
{
    public function testIncludeAllAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderAll(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeAll(\'foo\')'));
        $this->assertEquals('<?php echo $__env->renderAll(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeAll(name(foo))'));
    }
}
