<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Tests\View\Blade\AbstractBladeTestCase;

class BladeLogTest extends AbstractBladeTestCase
{
    public function testLogAreCompiled()
    {
        $this->assertSame("<?php Log::info('User authenticated.'); ?>", $this->compiler->compileString('@log(\'User authenticated.\')'));
    }
}
