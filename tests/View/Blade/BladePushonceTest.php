<?php

namespace Illuminate\Tests\View\Blade;

class BladePushonceTest extends AbstractBladeTestCase
{
    public function testPushonceIsCompiled()
    {
        $string = '@pushonce(\'foo\')
test
@endpushonce';
        $expected = '<?php $__env->startPushOnce(\'foo\'); ?>
test
<?php $__env->stopPushOnce(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
