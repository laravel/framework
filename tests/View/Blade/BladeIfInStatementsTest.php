<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfInStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@in(2, [1, 2, 3])
breeze
@endin';
        $expected = '<?php if(in_array(2, [1, 2, 3])): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
