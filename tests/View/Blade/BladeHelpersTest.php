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
    }

    public function testRouteCompiler()
    {
        $this->assertSame('<?php echo route("foo"); ?>', $this->compiler->compileString('@route("foo")'));
        $this->assertSame('<?php echo route("foo", ["user_id" => 1]); ?>', $this->compiler->compileString('@route("foo", ["user_id" => 1])'));
        $this->assertSame('<?php echo route("foo", ["user_id" => 1], true); ?>', $this->compiler->compileString('@route("foo", ["user_id" => 1], true)'));
        $this->assertSame('<?php echo route("foo", 1, false); ?>', $this->compiler->compileString('@route("foo", 1, false)'));
    }
}
