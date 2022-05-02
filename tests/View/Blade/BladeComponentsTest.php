<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use Mockery as m;

class BladeComponentsTest extends AbstractBladeTestCase
{
    public function testComponentsAreCompiled()
    {
        $this->assertSame('<?php $__env->startComponent(\'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@component(\'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->startComponent(\'foo\'); ?>', $this->compiler->compileString('@component(\'foo\')'));
    }

    public function testClassComponentsAreCompiled()
    {
        $this->assertSame('<?php if (isset($component)) { $__componentOriginal35bda42cbf6f9717b161c4f893644ac7a48b0d98 = $component; } ?>
<?php $component = $__env->getContainer()->make(Test::class, ["foo" => "bar"] + (isset($attributes) ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName(\'test\'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>', $this->compiler->compileString('@component(\'Test::class\', \'test\', ["foo" => "bar"])'));
    }

    public function testEndComponentsAreCompiled()
    {
        $this->compiler->newComponentHash('foo');

        $this->assertSame('<?php echo $__env->renderComponent(); ?>', $this->compiler->compileString('@endcomponent'));
    }

    public function testEndComponentClassesAreCompiled()
    {
        $this->compiler->newComponentHash('foo');

        $this->assertSame('<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33)): ?>
<?php $component = $__componentOriginal0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33; ?>
<?php unset($__componentOriginal0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33); ?>
<?php endif; ?>', $this->compiler->compileString('@endcomponentClass'));
    }

    public function testSlotsAreCompiled()
    {
        $this->assertSame('<?php $__env->slot(\'foo\', null, ["foo" => "bar"]); ?>', $this->compiler->compileString('@slot(\'foo\', null, ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->slot(\'foo\'); ?>', $this->compiler->compileString('@slot(\'foo\')'));
    }

    public function testEndSlotsAreCompiled()
    {
        $this->assertSame('<?php $__env->endSlot(); ?>', $this->compiler->compileString('@endslot'));
    }

    public function testPropsAreExtractedFromParentAttributesCorrectlyForClassComponents()
    {
        $attributes = new ComponentAttributeBag(['foo' => 'baz', 'other' => 'ok']);

        $component = m::mock(Component::class);
        $component->shouldReceive('withName', 'test');
        $component->shouldReceive('shouldRender')->andReturn(false);

        $__env = m::mock(\Illuminate\View\Factory::class);
        $__env->shouldReceive('getContainer->make')->with('Test', ['foo' => 'bar', 'other' => 'ok'])->andReturn($component);

        $template = $this->compiler->compileString('@component(\'Test::class\', \'test\', ["foo" => "bar"])');

        ob_start();
        eval(" ?> $template <?php endif; ");
        ob_get_clean();
    }
}
