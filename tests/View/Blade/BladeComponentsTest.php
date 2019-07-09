<?php

namespace Illuminate\Tests\View\Blade;

class BladeComponentsTest extends AbstractBladeTestCase
{
    public function testComponentsAreCompiled()
    {
        $this->assertEquals('<?php $__env->startComponent(\'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@component(\'foo\', ["foo" => "bar"])'));
        $this->assertEquals('<?php $__env->startComponent(\'foo\'); ?>', $this->compiler->compileString('@component(\'foo\')'));
    }

    public function testEndComponentsAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderComponent(); ?>', $this->compiler->compileString('@endcomponent'));
    }

    public function testSlotsAreCompiled()
    {
        $this->assertEquals('<?php $__env->slot(\'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@slot(\'foo\', ["foo" => "bar"])'));
        $this->assertEquals('<?php $__env->slot(\'foo\'); ?>', $this->compiler->compileString('@slot(\'foo\')'));
    }

    public function testEndSlotsAreCompiled()
    {
        $this->assertEquals('<?php $__env->endSlot(); ?>', $this->compiler->compileString('@endslot'));
    }

    public function testScopedSlotsAreCompiled()
    {
        $this->assertEquals('<?php $__env->scopedSlot(\'foo\', function ($bar) { ?>', $this->compiler->compileString('@scopedslot(\'foo\', function ($bar))'));
    }

    public function testEndScopedSlotsAreCompiled()
    {
        $this->assertEquals('<?php }); ?>', $this->compiler->compileString('@endscopedslot'));
    }
}
