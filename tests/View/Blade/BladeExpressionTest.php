<?php

namespace Illuminate\Tests\View\Blade;

class BladeExpressionTest extends AbstractBladeTestCase
{
    public function testExpressionsOnTheSameLine()
    {
        $this->assertEquals('<?php echo app(\'translator\')->getFromJson(foo(bar(baz(qux(breeze()))))); ?> space () <?php echo app(\'translator\')->getFromJson(foo(bar)); ?>', $this->compiler->compileString('@lang(foo(bar(baz(qux(breeze()))))) space () @lang(foo(bar))'));
    }

    public function testExpressionWithinHTML()
    {
        $this->assertEquals('<html <?php echo e($foo); ?>>', $this->compiler->compileString('<html {{ $foo }}>'));
        $this->assertEquals('<html<?php echo e($foo); ?>>', $this->compiler->compileString('<html{{ $foo }}>'));
        $this->assertEquals('<html <?php echo e($foo); ?> <?php echo app(\'translator\')->getFromJson(\'foo\'); ?>>', $this->compiler->compileString('<html {{ $foo }} @lang(\'foo\')>'));
    }
}
