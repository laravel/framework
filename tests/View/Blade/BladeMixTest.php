<?php

namespace Illuminate\Tests\View\Blade;

class BladeMixTest extends AbstractBladeTestCase
{
    public function testMixesAreCompiled()
    {
        $this->assertEquals('<?php echo mix("js/app.js"); ?>', $this->compiler->compileString('@mix("js/app.js")'));
    }
}
