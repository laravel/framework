<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfEmptyStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@empty ($test)
breeze
@endempty';
        $expected = '<?php if(empty($test)): ?>
breeze
<?php endif; ?>';
        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
