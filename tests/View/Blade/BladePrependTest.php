<?php

namespace Illuminate\Tests\View\Blade;

class BladePrependTest extends AbstractBladeTestCase
{
    public function testPrependIsCompiled()
    {
        $string = '@prepend(\'foo\')
bar
@endprepend';
        $expected = '<?php $__env->startPrepend(\'foo\'); ?>
bar
<?php $__env->stopPrepend(); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
