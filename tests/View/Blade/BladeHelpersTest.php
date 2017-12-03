<?php

namespace Illuminate\Tests\View\Blade;

class BladeHelpersTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertEquals('<?php echo csrf_field(); ?>', $this->compiler->compileString('@csrf'));
        $this->assertEquals('<?php echo method_field(\'patch\'); ?>', $this->compiler->compileString("@method('patch')"));
    }

    public function testDumpStatementsAreCompiled()
    {
        $this->assertEquals('<?php dump($foo); ?>', $this->compiler->compileString('@dump($foo)'));
        $this->assertEquals('<?php dump($foo, $bar); ?>', $this->compiler->compileString('@dump($foo, $bar)'));
    }
}
