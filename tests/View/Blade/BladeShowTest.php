<?php

namespace Illuminate\Tests\View\Blade;

class BladeShowTest extends AbstractBladeTestCase
{
    public function testShowsAreCompiled(): void
    {
        $this->assertEquals('<?php echo $__env->yieldSection(); ?>', $this->compiler->compileString('@show'));
    }
}
