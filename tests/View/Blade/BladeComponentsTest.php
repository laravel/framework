<?php

namespace Illuminate\Tests\View\Blade;

class BladeComponentsTest extends AbstractBladeTestCase
{
    public function testComponentsAreCompiled()
    {
        $this->assertSame('<?php $__env->startComponent(\'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@component(\'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->startComponent(\'foo\'); ?>', $this->compiler->compileString('@component(\'foo\')'));
    }

    public function testEndComponentsAreCompiled()
    {
        $this->assertSame('<?php echo $__env->renderComponent(); ?>', $this->compiler->compileString('@endcomponent'));
    }

    public function testSlotsAreCompiled()
    {
        $this->assertSame('<?php $__env->slot(\'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@slot(\'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->slot(\'foo\'); ?>', $this->compiler->compileString('@slot(\'foo\')'));
    }

    public function testEndSlotsAreCompiled()
    {
        $this->assertSame('<?php $__env->endSlot(); ?>', $this->compiler->compileString('@endslot'));
    }
}
