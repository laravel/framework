<?php

namespace Illuminate\Tests\View\Blade;

class BladeHelpersTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertSame('<?php echo csrf_field(); ?>', $this->compiler->compileString('@csrf'));
        $this->assertSame('<?php echo method_field(\'patch\'); ?>', $this->compiler->compileString("@method('patch')"));
        $this->assertSame('<?php dd($var1); ?>', $this->compiler->compileString('@dd($var1)'));
        $this->assertSame('<?php dd($var1, $var2); ?>', $this->compiler->compileString('@dd($var1, $var2)'));
        $this->assertSame('<?php dump($var1, $var2); ?>', $this->compiler->compileString('@dump($var1, $var2)'));
        $this->assertSame('<?php echo app(\'Illuminate\Foundation\Vite\')(); ?>', $this->compiler->compileString('@vite'));
        $this->assertSame('<?php echo app(\'Illuminate\Foundation\Vite\')(); ?>', $this->compiler->compileString('@vite()'));
        $this->assertSame('<?php echo app(\'Illuminate\Foundation\Vite\')(\'resources/js/app.js\'); ?>', $this->compiler->compileString('@vite(\'resources/js/app.js\')'));
        $this->assertSame('<?php echo app(\'Illuminate\Foundation\Vite\')([\'resources/js/app.js\']); ?>', $this->compiler->compileString('@vite([\'resources/js/app.js\'])'));
        $this->assertSame('<?php echo app(\'Illuminate\Foundation\Vite\')->reactRefresh(); ?>', $this->compiler->compileString('@viteReactRefresh'));
    }
}
