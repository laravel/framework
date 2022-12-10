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
        $this->assertSame('<?php echo app(\'vite\')(); ?>', $this->compiler->compileString('@vite'));
        $this->assertSame('<?php echo app(\'vite\')(); ?>', $this->compiler->compileString('@vite()'));
        $this->assertSame('<?php echo app(\'vite\')(\'resources/js/app.js\'); ?>', $this->compiler->compileString('@vite(\'resources/js/app.js\')'));
        $this->assertSame('<?php echo app(\'vite\')([\'resources/js/app.js\']); ?>', $this->compiler->compileString('@vite([\'resources/js/app.js\'])'));
        $this->assertSame('<?php echo app(\'vite\')->app()->toHtml(); ?>', $this->compiler->compileString('@viteApp'));
        $this->assertSame('<?php echo app(\'vite\')->app()->toHtml(); ?>', $this->compiler->compileString('@viteApp()'));
        $this->assertSame('<?php echo app(\'vite\')->app(\'app\')->toHtml(); ?>', $this->compiler->compileString('@viteApp(\'app\')'));
        $this->assertSame('<?php echo app(\'vite\')->reactRefresh(); ?>', $this->compiler->compileString('@viteReactRefresh'));
    }
}
