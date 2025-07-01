<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfBlankFilledStatementsTest extends AbstractBladeTestCase
{
    public function testBlankStatementsAreCompiled()
    {
        $string = '@blank ($test)
breeze
@endblank';
        $expected = '<?php if(blank($test)): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testFilledStatementsAreCompiled()
    {
        $string = '@filled ($test)
breeze
@endfilled';
        $expected = '<?php if(filled($test)): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
