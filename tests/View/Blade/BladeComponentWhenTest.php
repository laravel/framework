<?php

namespace Illuminate\Tests\View\Blade;

class BladeComponentWhenTest extends AbstractBladeTestCase
{
    public function testComponentWhensAreCompiled()
    {
        $this->assertSame('<?php $__env->startComponentWhen(true, \'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@componentWhen(true, \'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->startComponentWhen(true, \'foo\'); ?>', $this->compiler->compileString('@componentWhen(true, \'foo\')'));
    }
}
