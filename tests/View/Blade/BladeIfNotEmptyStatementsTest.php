<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfNotEmptyStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@notEmpty ($test)
breeze
@endNotEmpty';
        $expected = '<?php if(!empty($test)): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
