<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\DynamicComponent;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DynamicComponentTest extends TestCase
{
    #[DataProvider('invalidComponentNames')]
    public function testInvalidNamesAreRejectedBeforeComponentResolution(string $name): void
    {
        $component = new DynamicComponent($name);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dynamic component name.');

        ($component->render())([]);
    }

    public static function invalidComponentNames(): array
    {
        return [
            [''],
            ['panel with-space'],
            ["panel\n"],
            ["panel\0"],
            ['panel{{ }}'],
            ['panel{!! !!}'],
            ['panel@'],
            ['panel<?'],
            ['panel>'],
            ['panel/'],
            ['panel"'],
            ["panel'"],
            ['mail::panel with-space'],
        ];
    }

    public function testNamesAreValidatedWhenTheRenderCallbackRuns(): void
    {
        $component = new DynamicComponent('panel');
        $render = $component->render();
        $component->component = 'panel with-space';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dynamic component name.');

        $render([]);
    }

    public function testInvalidBackedEnumNamesAreRejected(): void
    {
        $component = new DynamicComponent(DynamicComponentName::Invalid);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dynamic component name.');

        ($component->render())([]);
    }

    public function testValidComponentNamesMatchTheTagGrammar(): void
    {
        foreach (['alert', 'Alert2', 'input_label', 'forms.input-label', 'package::alert', 'mail::panel'] as $name) {
            $this->assertTrue(ComponentTagCompiler::isValidComponentName($name));
        }

        $component = new DynamicComponent(DynamicComponentName::Panel);

        $this->assertSame('panel', $component->component);
        $this->assertTrue(ComponentTagCompiler::isValidComponentName($component->component));
    }

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

enum DynamicComponentName: string
{
    case Panel = 'panel';
    case Invalid = 'panel with-space';
}
