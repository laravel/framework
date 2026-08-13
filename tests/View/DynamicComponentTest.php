<?php

namespace Illuminate\Tests\View;

use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\DynamicComponent;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DynamicComponentTest extends TestCase
{
    public function testCompileSlotsExcludesDefaultSlot(): void
    {
        $component = new DynamicComponent('alert');

        $method = new ReflectionMethod(DynamicComponent::class, 'compileSlots');
        $result = $method->invoke($component, [
            '__default' => (object) ['attributes' => new ComponentAttributeBag],
            'title' => (object) ['attributes' => new ComponentAttributeBag],
        ]);

        $this->assertStringNotContainsString('__default', $result);
        $this->assertStringContainsString('<x-slot name="title"', $result);
        $this->assertStringContainsString('{{ $title }}', $result);
    }

    public function testCompileSlotsReturnsEmptyStringWhenOnlyDefaultSlotIsPresent(): void
    {
        $component = new DynamicComponent('alert');

        $method = new ReflectionMethod(DynamicComponent::class, 'compileSlots');
        $result = $method->invoke($component, [
            '__default' => (object) ['attributes' => new ComponentAttributeBag],
        ]);

        $this->assertSame('', $result);
    }
}
