<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfNotinStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@notin(2, [1, 2, 3])
breeze
@endnotin';
        $expected = '<?php if(! in_array(2, [1, 2, 3])): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
