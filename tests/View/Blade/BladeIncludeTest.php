<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeTest extends AbstractBladeTestCase
{
    public function testIncludesAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@include(\'foo\')'));
        $this->assertEquals('<?php echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@include(name(foo))'));
    }
}
