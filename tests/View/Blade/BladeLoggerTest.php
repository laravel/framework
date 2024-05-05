<?php

namespace Illuminate\Tests\View\Blade;

class BladeLoggerTest extends AbstractBladeTestCase
{
    public function testLoggerAreCompiled()
    {
        $this->assertSame('<?php logger(\'User authenticated.\', [\'user_id\' => 1]); ?>', $this->compiler->compileString('@logger(\'User authenticated.\', [\'user_id\' => 1])'));
        $this->assertSame('<?php logger(\'User authenticated.\'); ?>', $this->compiler->compileString('@logger(\'User authenticated.\')'));
    }
}
