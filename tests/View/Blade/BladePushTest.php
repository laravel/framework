<?php

namespace Illuminate\Tests\View\Blade;

class BladePushTest extends AbstractBladeTestCase
{
    public function testPushIsCompiled()
    {
        $string = '@push(\'foo\')
test
@endpush';
        $expected = '<?php $__env->startPush(\'foo\'); ?>
test
<?php $__env->stopPush(); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPushOnceIsCompiled()
    {
        $string = '@pushOnce(\'foo\', \'bar\')
test
@endpushOnce';

        $expected = '<?php $__env->startPush(\'foo\');
if (! $__env->hasRenderedOnce(\'bar\')):
$__env->markAsRenderedOnce(\'bar\'); ?>
test
<?php endif; $__env->stopPush(); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
