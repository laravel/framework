<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludeFirstTest extends AbstractBladeTestCase
{
    public function testIncludeFirstsAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->first(["one", "two"], \Illuminate\Support\Arr::except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@includeFirst(["one", "two"])'));
        $this->assertEquals('<?php echo $__env->first(["one", "two"], ["foo" => "bar"], \Illuminate\Support\Arr::except(get_defined_vars(), array(\'__data\', \'__path\')))->render(); ?>', $this->compiler->compileString('@includeFirst(["one", "two"], ["foo" => "bar"])'));
    }
}
