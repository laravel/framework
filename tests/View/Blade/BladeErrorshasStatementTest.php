<?php

namespace Illuminate\Tests\View\Blade;

class BladeErrorshasStatementTest extends AbstractBladeTestCase
{
    public function testErrorshasAreCompiled()
    {
        $statement = '@errorshas(\'password\')
                          foo bar
                      @enderrorshas';

        $expected = '<?php if($errors->has(\'password\')): ?>
                          foo bar
                      <?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($statement));
    }

    public function testErrorshasWithArrayAreCompiled()
    {
        $statement = '@errorshas([\'password\', \'email\'])
                          foo bar
                      @enderrorshas';

        $expected = '<?php if($errors->has([\'password\', \'email\'])): ?>
                          foo bar
                      <?php endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($statement));
    }
}
