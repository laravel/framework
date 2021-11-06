<?php

namespace Illuminate\Tests\View\Blade;

class BladeCountStatementsTest extends AbstractBladeTestCase
{
    public function testCountStatementsAreCompiled()
    {
        $string = '@count ($test)
breeze
@endcount';
        $expected = '<?php if(count($test)): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
