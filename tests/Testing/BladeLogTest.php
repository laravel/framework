<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Tests\View\Blade\AbstractBladeTestCase;

class BladeLogTest extends AbstractBladeTestCase
{
    public function testLogAreCompiled()
    {
        $this->assertSame("<?php Log::info('User authenticated.'); ?>", $this->compiler->compileString('@log(\'User authenticated.\')'));
        $this->assertSame("<?php Log::error('Error occurred.'); ?>", $this->compiler->compileString('@log(\'Error occurred.\', \'error\')'));
        $this->assertSame("<?php Log::debug('Debug occurred.'); ?>", $this->compiler->compileString('@log(\'Debug occurred.\', \'debug\')'));
        $this->assertSame("<?php Log::alert('Alert occurred.'); ?>", $this->compiler->compileString('@log(\'Alert occurred.\', \'alert\')'));
        $this->assertSame("<?php Log::critical('Critical occurred.'); ?>", $this->compiler->compileString('@log(\'Critical occurred.\', \'critical\')'));
        $this->assertSame("<?php Log::notice('Notice occurred.'); ?>", $this->compiler->compileString('@log(\'Notice occurred.\', \'notice\')'));
        $this->assertSame("<?php Log::emergency('Emergency occurred.'); ?>", $this->compiler->compileString('@log(\'Emergency occurred.\', \'emergency\')'));
    }
}
