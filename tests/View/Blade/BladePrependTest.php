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

    public function testPrependOnceIsCompiled()
    {
        $string = '@prependOnce(\'foo\', \'bar\')
test
@endPrependOnce';

        $expected = '<?php if (! $__env->hasRenderedOnce(\'bar\')): $__env->markAsRenderedOnce(\'bar\');
$__env->startPrepend(\'foo\'); ?>
test
<?php $__env->stopPrepend(); endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
