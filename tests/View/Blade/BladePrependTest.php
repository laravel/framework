<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Support\Str;

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

    public function testPrependOnceIsCompiledWhenIdIsMissing()
    {
        Str::createUuidsUsing(fn () => 'e60e8f77-9ac3-4f71-9f8e-a044ef481d7f');

        $string = '@prependOnce(\'foo\')
test
@endPrependOnce';

        $expected = '<?php if (! $__env->hasRenderedOnce(\'e60e8f77-9ac3-4f71-9f8e-a044ef481d7f\')): $__env->markAsRenderedOnce(\'e60e8f77-9ac3-4f71-9f8e-a044ef481d7f\');
$__env->startPrepend(\'foo\'); ?>
test
<?php $__env->stopPrepend(); endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
