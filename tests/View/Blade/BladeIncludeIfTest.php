<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeIfTest extends AbstractBladeTestCase
{
    public function testIncludeIfsAreCompiled()
    {
        $this->assertEquals('<?php if ($__env->exists(\'foo\')) echo $__env->make(\'foo\', array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@includeIf(\'foo\')'));
        $this->assertEquals('<?php if ($__env->exists(name(foo))) echo $__env->make(name(foo), array_except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@includeIf(name(foo))'));
    }
}
