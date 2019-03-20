<?php

namespace Illuminate\Tests\View\Blade;

class BladeErrorsanyStatementTest extends AbstractBladeTestCase
{
    public function testErrorsanyAreCompiled()
    {
        $statement = '@errorsany([\'password\', \'email\'])
                          foo bar
                      @enderrorshas';

        $expected = '<?php if($errors->hasAny([\'password\', \'email\'])): ?>
                          foo bar
                      <?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($statement));
    }
}
