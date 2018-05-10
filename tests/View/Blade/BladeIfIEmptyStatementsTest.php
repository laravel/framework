<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfIEmptyStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@empty ($test)
breeze
@endempty';
        $expected = '<?php if(empty($test)): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
