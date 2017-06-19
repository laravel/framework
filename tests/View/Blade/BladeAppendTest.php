<?php

namespace Illuminate\Tests\View\Blade;

class BladeAppendTest extends AbstractBladeTestCase
{
    public function testAppendSectionsAreCompiled()
    {
        $this->assertEquals('<?php $__env->appendSection(); ?>', $this->compiler->compileString('@append'));
    }
}
