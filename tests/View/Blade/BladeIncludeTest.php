<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeTest extends AbstractBladeTestCase
{
    public function testIncludesAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@include(\'foo\')'));
        $this->assertEquals('<?php echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@include(name(foo))'));
    }
}
