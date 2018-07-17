<?php

namespace Illuminate\Tests\View\Blade;

class BladeAssetTest extends AbstractBladeTestCase
{
    public function testAssetsAreCompiled()
    {
        $this->assertEquals('<?php echo asset(""); ?>', $this->compiler->compileString('@asset("")'));
    }
}
