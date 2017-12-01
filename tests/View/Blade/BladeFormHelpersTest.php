<?php

namespace Illuminate\Tests\View\Blade;

class BladeFormHelpersTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertEquals('<?php echo csrf_field(); ?>', $this->compiler->compileString('@csrf'));
        $this->assertEquals('<?php echo method_field(\'patch\'); ?>', $this->compiler->compileString("@method('patch')"));
    }
}
