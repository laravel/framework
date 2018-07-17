<?php

namespace Illuminate\Tests\View\Blade;

class BladeHelpersTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertEquals('<?php echo csrf_field(); ?>', $this->compiler->compileString('@csrf'));
        $this->assertEquals('<?php echo method_field(\'patch\'); ?>', $this->compiler->compileString("@method('patch')"));
        $this->assertEquals('<?php dd($var1); ?>', $this->compiler->compileString('@dd($var1)'));
        $this->assertEquals('<?php dd($var1, $var2); ?>', $this->compiler->compileString('@dd($var1, $var2)'));
        $this->assertEquals('<?php dump($var1, $var2); ?>', $this->compiler->compileString('@dump($var1, $var2)'));
    }
}
