<?php

namespace Illuminate\Tests\View\Blade;

class BladeElseIssetStatementsTest extends AbstractBladeTestCase
{
    public function testElseIssetStatementsAreCompiled()
    {
        $string = '@isset ($test)
breeze
@elseisset($test1)
boom
@endisset';
        $expected = '<?php if(isset($test)): ?>
breeze
<?php elseif(isset($test1)): ?>
boom
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
