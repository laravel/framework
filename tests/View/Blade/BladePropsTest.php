<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\ComponentAttributeBag;

class BladePropsTest extends AbstractBladeTestCase
{
    public function testPropsAreCompiled()
    {
        $this->assertSame('<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([\'one\' => true, \'two\' => \'string\']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([\'one\' => true, \'two\' => \'string\']); ?>
<?php foreach (array_filter(([\'one\' => true, \'two\' => \'string\']), \'is_string\', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>', $this->compiler->compileString('@props([\'one\' => true, \'two\' => \'string\'])'));
    }

    public function testPropsAreExtractedFromParentAttributesCorrectly()
    {
        $test1 = $test2 = $test4 = null;

        $attributes = new ComponentAttributeBag(['test1' => 'value1', 'test2' => 'value2', 'test3' => 'value3']);

        $template = $this->compiler->compileString('@props([\'test1\' => \'default\', \'test2\', \'test4\' => \'default\'])');

        ob_start();
        eval(" ?> $template <?php ");
        ob_get_clean();

        $this->assertSame($test1, 'value1');
        $this->assertSame($test2, 'value2');
        $this->assertFalse(isset($test3));
        $this->assertSame($test4, 'default');

        $this->assertNull($attributes->get('test1'));
        $this->assertNull($attributes->get('test2'));
        $this->assertSame($attributes->get('test3'), 'value3');
    }
}
