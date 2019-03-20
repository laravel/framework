<?php

namespace Illuminate\Tests\View\Blade;

class BladeErrorsfirstStatementTest extends AbstractBladeTestCase
{
    public function testErrorsfirstAreCompiled()
    {
        $this->assertEquals('<?php echo $errors->first(\'email\'); ?>', $this->compiler->compileString('@errorsfirst(\'email\')'));
    }
}
