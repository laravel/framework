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
        $this->assertSame('<?php echo url(\'/home\'); ?>', $this->compiler->compileString('@url(\'/home\')'));
        $this->assertSame('<?php echo url(\'/home\', [1]); ?>', $this->compiler->compileString('@url(\'/home\', [1])'));
        $this->assertSame('<?php echo route(\'home.index\'); ?>', $this->compiler->compileString('@route(\'home.index\')'));
        $this->assertSame('<?php echo route(\'user.profile\', [\'id\' => 1]); ?>', $this->compiler->compileString('@route(\'user.profile\', [\'id\' => 1])'));
        $this->assertSame('<?php echo old(\'name\'); ?>', $this->compiler->compileString('@old(\'name\')'));
        $this->assertSame('<?php echo old(\'name\', \'test\'); ?>', $this->compiler->compileString('@old(\'name\', \'test\')'));
        $this->assertSame('<?php echo asset(\'/css/style.css\'); ?>', $this->compiler->compileString('@asset(\'/css/style.css\')'));
    }
}
