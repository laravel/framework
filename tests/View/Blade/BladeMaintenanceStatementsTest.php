<?php

namespace Illuminate\Tests\View\Blade;

class BladeMaintenanceStatementsTest extends AbstractBladeTestCase
{
    public function testMaintenanceStatementsAreCompiled()
    {
        $string = '@maintenance
breeze
@else
boom
@endmaintenance';
        $expected = "<?php if(app()->isDownForMaintenance()): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
