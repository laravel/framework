<?php

namespace Illuminate\Tests\View\Blade;

class BladePrependonceTest extends AbstractBladeTestCase
{
    public function testPrependonceIsCompiled()
    {
        $string = '@prependonce(\'foo\')
bar
@endprependonce';
        $expected = '<?php $__env->startPrependOnce(\'foo\'); ?>
bar
<?php $__env->stopPrependOnce(); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
