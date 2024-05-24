<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfFilledStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
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
