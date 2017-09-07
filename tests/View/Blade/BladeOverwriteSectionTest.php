<?php

namespace Illuminate\Tests\View\Blade;

class BladeOverwriteSectionTest extends AbstractBladeTestCase
{
    public function testOverwriteSectionsAreCompiled()
    {
        $this->assertEquals('<?php $__env->stopSection(true); ?>', $this->compiler->compileString('@overwrite'));
    }
}
