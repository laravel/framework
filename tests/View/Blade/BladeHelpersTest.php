<?php

namespace Illuminate\Tests\View\Blade;

class BladeHelpersTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertEquals('<?php echo csrf_field(); ?>', $this->compiler->compileString('@csrf'));
        $this->assertEquals('<?php echo method_field(\'patch\'); ?>', $this->compiler->compileString("@method('patch')"));
        $this->assertEquals('<?php dd($var1); ?>', $this->compiler->compileString('@dd($var1)'));
        $this->assertEquals('<?php dd($var1, $var2); ?>', $this->compiler->compileString('@dd($var1, $var2)'));
    }

    public function testHasSectionStatementsAreCompiled()
    {
        $string = '@hasError("email")
This e-mail is incorrect
@endhasError';
        $expected = '<?php if ($errors->has("email")): ?>
This e-mail is incorrect
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
