<?php

namespace Illuminate\Tests\View\Blade;

class BladeWhenTest extends AbstractBladeTestCase
{
    public function testWhenIsCompiled()
    {
        $this->assertSame('<?php echo when($var1, $var2, $var3); ?>', $this->compiler->compileString('@when($var1, $var2, $var3)'));
    }
}
