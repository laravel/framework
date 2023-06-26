<?php

namespace Illuminate\Tests\View\Blade;

class BladeSlotsTest extends AbstractBladeTestCase
{
    public function testSlotssAreCompiled()
    {
        $this->assertSame('<?php foreach (([\'optional_slot\', \'another_slot\' => [\'contents\' => \'string\']]) as $__key => $__value) {
    $__key = is_numeric($__key) ? $__value : $__key;
    $__value = !is_array($__value) && !$__value instanceof \ArrayAccess ? [] : $__value;
    if (!isset($$__key) || is_string($$__key)) {
        $$__key = new \Illuminate\View\ComponentSlot($$__key ?? $__value[\'contents\'] ?? \'\', $__value[\'attributes\'] ?? []);
    }
} ?>
<?php $attributes ??= new \\Illuminate\\View\\ComponentAttributeBag; ?>
<?php $attributes = $attributes->exceptProps([\'optional_slot\', \'another_slot\' => [\'contents\' => \'string\']]); ?>', $this->compiler->compileString('@slots([\'optional_slot\', \'another_slot\' => [\'contents\' => \'string\']])'));
    }
}
