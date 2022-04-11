<?php

namespace Illuminate\Tests\View\Blade;

class BladeImplicitCsrfTest extends AbstractBladeTestCase
{
    public function testImplicitCsrfTokenIsCompiled()
    {
        $string = '<form method="POST">
<input type="text">
</form>';
        $expected = '<form method="POST">
<?php echo csrf_field(); ?>
<input type="text">
</form>';

        $this->assertSame($expected, $this->compiler->compileString($string));
    }

    public function testImplicitCsrfTokenIsNotCompiledWhenNoCsrfHelperIsPresent()
    {
        $string = '<form method="POST">
@nocsrf
<input type="text">
</form>';
        $expected = '<form method="POST">

<input type="text">
</form>';

        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
