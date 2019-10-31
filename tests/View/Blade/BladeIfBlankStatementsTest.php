<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfBlankStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@blank($test)
breeze
@endblank';
        $expected = '<?php if(blank($test)): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
