<?php

namespace Illuminate\Tests\Support;

use Attribute;
use Illuminate\Support\Attr;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;

class SupportAttrTest extends TestCase
{
    protected function attr(callable $factory, bool $instancesOf, ?bool $recursiveAll): Attr
    {
        $attr = $factory();

        if ($instancesOf) {
            $attr->instancesOf();
        }

        if ($recursiveAll !== null) {
            $attr->recursive($recursiveAll);
        }

        return $attr;
    }

    protected function attributeValues(array $attributes): array
    {
        return array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance()->value, $attributes);
    }

    protected function instanceValues(array $instances): array
    {
        return array_map(fn (SupportAttrTestBaseAttribute $instance) => $instance->value, $instances);
    }

    public static function allTargetsProvider(): array
    {
        return [
            'class exact' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), false, null, ['child-base']],
            'class instances of' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), true, null, ['child-base', 'child-child']],
            'class recursive first exact' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), false, false, ['child-base']],
            'class recursive first instances of' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), true, false, ['child-base', 'child-child']],
            'class recursive all exact' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), false, true, ['child-base', 'parent-base']],
            'class recursive all instances of' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), true, true, ['child-base', 'child-child', 'parent-base', 'parent-child']],
            'class recursive first finds parent exact' => [fn () => Attr::onClass(SupportAttrTestChildClassWithoutAttributes::class), false, false, ['parent-base']],
            'class recursive first finds parent instances of' => [fn () => Attr::onClass(SupportAttrTestChildClassWithoutAttributes::class), true, false, ['parent-base', 'parent-child']],
            'object exact' => [fn () => Attr::onObject(new SupportAttrTestChildClass), false, null, ['child-base']],
            'object instances of' => [fn () => Attr::onObject(new SupportAttrTestChildClass), true, null, ['child-base', 'child-child']],
            'object recursive all exact' => [fn () => Attr::onObject(new SupportAttrTestChildClass), false, true, ['child-base', 'parent-base']],
            'object recursive all instances of' => [fn () => Attr::onObject(new SupportAttrTestChildClass), true, true, ['child-base', 'child-child', 'parent-base', 'parent-child']],
            'class from object instances of' => [fn () => Attr::onClass(new SupportAttrTestChildClass), true, null, ['child-base', 'child-child']],
            'method exact' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), false, null, ['method-base']],
            'method instances of' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), true, null, ['method-base', 'method-child']],
            'method recursive exact' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), false, false, ['method-base']],
            'method recursive instances of' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), true, false, ['method-base', 'method-child']],
            'method recursive all exact' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), false, true, ['method-base', 'parent-method-base']],
            'method recursive all instances of' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), true, true, ['method-base', 'method-child', 'parent-method-base', 'parent-method-child']],
            'method recursive first finds parent exact' => [fn () => Attr::onMethod(SupportAttrTestChildTargetWithoutAttributes::class, 'method'), false, false, ['parent-method-base']],
            'method recursive first finds parent instances of' => [fn () => Attr::onMethod(SupportAttrTestChildTargetWithoutAttributes::class, 'method'), true, false, ['parent-method-base', 'parent-method-child']],
            'method from object instances of' => [fn () => Attr::onMethod(new SupportAttrTestTarget, 'method'), true, null, ['method-base', 'method-child']],
            'property exact' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), false, null, ['property-base']],
            'property instances of' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), true, null, ['property-base', 'property-child']],
            'property recursive exact' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), false, false, ['property-base']],
            'property recursive instances of' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), true, false, ['property-base', 'property-child']],
            'property recursive all exact' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), false, true, ['property-base', 'parent-property-base']],
            'property recursive all instances of' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), true, true, ['property-base', 'property-child', 'parent-property-base', 'parent-property-child']],
            'property recursive first finds parent exact' => [fn () => Attr::onProperty(SupportAttrTestChildTargetWithoutAttributes::class, 'property'), false, false, ['parent-property-base']],
            'property recursive first finds parent instances of' => [fn () => Attr::onProperty(SupportAttrTestChildTargetWithoutAttributes::class, 'property'), true, false, ['parent-property-base', 'parent-property-child']],
            'property from object instances of' => [fn () => Attr::onProperty(new SupportAttrTestTarget, 'property'), true, null, ['property-base', 'property-child']],
        ];
    }

    public static function missingQueryModifiersProvider(): array
    {
        return [
            'class exact' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), false, null],
            'class instances of' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), true, null],
            'class recursive first' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), false, false],
            'class recursive all instances of' => [fn () => Attr::onClass(SupportAttrTestChildClass::class), true, true],
            'object recursive all instances of' => [fn () => Attr::onObject(new SupportAttrTestChildClass), true, true],
            'method recursive all instances of' => [fn () => Attr::onMethod(SupportAttrTestTarget::class, 'method'), true, true],
            'property recursive all instances of' => [fn () => Attr::onProperty(SupportAttrTestTarget::class, 'property'), true, true],
        ];
    }

    #[DataProvider('allTargetsProvider')]
    public function test_all_retrieves_attribute_reflections_for_target(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $attributes = $this->attr($factory, $instancesOf, $recursiveAll)->all(SupportAttrTestBaseAttribute::class);

        $this->assertContainsOnlyInstancesOf(ReflectionAttribute::class, $attributes);
        $this->assertSame($expected, $this->attributeValues($attributes));
    }

    #[DataProvider('allTargetsProvider')]
    public function test_instances_returns_attribute_instances(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $instances = $this->attr($factory, $instancesOf, $recursiveAll)
            ->instances(SupportAttrTestBaseAttribute::class);

        $this->assertContainsOnlyInstancesOf(SupportAttrTestBaseAttribute::class, $instances);
        $this->assertSame($expected, $this->instanceValues($instances));
    }

    #[DataProvider('allTargetsProvider')]
    public function test_collect_returns_attribute_instances_in_collection(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $collection = $this->attr($factory, $instancesOf, $recursiveAll)
            ->collect(SupportAttrTestBaseAttribute::class);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertContainsOnlyInstancesOf(SupportAttrTestBaseAttribute::class, $collection);
        $this->assertSame($expected, $collection->map->value->all());
    }

    #[DataProvider('allTargetsProvider')]
    public function test_first_returns_first_attribute_reflection(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $attribute = $this->attr($factory, $instancesOf, $recursiveAll)
            ->first(SupportAttrTestBaseAttribute::class);

        $this->assertInstanceOf(ReflectionAttribute::class, $attribute);
        $this->assertSame($expected[0], $attribute->newInstance()->value);
    }

    #[DataProvider('allTargetsProvider')]
    public function test_instance_returns_first_attribute_instance(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $instance = $this->attr($factory, $instancesOf, $recursiveAll)
            ->instance(SupportAttrTestBaseAttribute::class);

        $this->assertInstanceOf(SupportAttrTestBaseAttribute::class, $instance);
        $this->assertSame($expected[0], $instance->value);
    }

    #[DataProvider('allTargetsProvider')]
    public function test_has_returns_true_when_attribute_exists(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $this->assertTrue(
            $this->attr($factory, $instancesOf, $recursiveAll)
                ->has(SupportAttrTestBaseAttribute::class)
        );
    }

    #[DataProvider('allTargetsProvider')]
    public function test_missing_returns_false_when_attribute_exists(callable $factory, bool $instancesOf, ?bool $recursiveAll, array $expected): void
    {
        $this->assertFalse(
            $this->attr($factory, $instancesOf, $recursiveAll)
                ->missing(SupportAttrTestBaseAttribute::class)
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_all_returns_empty_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertSame(
            [],
            $this->attr($factory, $instancesOf, $recursiveAll)->all(SupportAttrTestMissingAttribute::class)
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_instances_returns_empty_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertSame(
            [],
            $this->attr($factory, $instancesOf, $recursiveAll)->instances(SupportAttrTestMissingAttribute::class)
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_collect_returns_empty_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertTrue(
            $this->attr($factory, $instancesOf, $recursiveAll)->collect(SupportAttrTestMissingAttribute::class)->isEmpty()
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_first_returns_null_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertNull(
            $this->attr($factory, $instancesOf, $recursiveAll)->first(SupportAttrTestMissingAttribute::class)
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_instance_returns_null_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertNull(
            $this->attr($factory, $instancesOf, $recursiveAll)->instance(SupportAttrTestMissingAttribute::class)
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_has_returns_false_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertFalse(
            $this->attr($factory, $instancesOf, $recursiveAll)->has(SupportAttrTestMissingAttribute::class)
        );
    }

    #[DataProvider('missingQueryModifiersProvider')]
    public function test_missing_returns_true_when_attribute_is_missing(callable $factory, bool $instancesOf, ?bool $recursiveAll): void
    {
        $this->assertTrue(
            $this->attr($factory, $instancesOf, $recursiveAll)->missing(SupportAttrTestMissingAttribute::class)
        );
    }

    public function test_instances_of_returns_same_attr_instance(): void
    {
        $attr = Attr::onClass(SupportAttrTestChildClass::class);

        $this->assertSame($attr, $attr->instancesOf());
    }

    public function test_recursive_returns_same_attr_instance(): void
    {
        $attr = Attr::onClass(SupportAttrTestChildClass::class);

        $this->assertSame($attr, $attr->recursive());
    }
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SupportAttrTestBaseAttribute
{
    public function __construct(public string $value)
    {
    }
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SupportAttrTestChildAttribute extends SupportAttrTestBaseAttribute
{
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class SupportAttrTestMissingAttribute
{
}

#[SupportAttrTestBaseAttribute('parent-base')]
#[SupportAttrTestChildAttribute('parent-child')]
class SupportAttrTestParentClass
{
}

#[SupportAttrTestBaseAttribute('child-base')]
#[SupportAttrTestChildAttribute('child-child')]
class SupportAttrTestChildClass extends SupportAttrTestParentClass
{
}

class SupportAttrTestChildClassWithoutAttributes extends SupportAttrTestParentClass
{
}

class SupportAttrTestParentTarget
{
    #[SupportAttrTestBaseAttribute('parent-property-base')]
    #[SupportAttrTestChildAttribute('parent-property-child')]
    public $property;

    #[SupportAttrTestBaseAttribute('parent-method-base')]
    #[SupportAttrTestChildAttribute('parent-method-child')]
    public function method(): void
    {
    }
}

class SupportAttrTestTarget extends SupportAttrTestParentTarget
{
    #[SupportAttrTestBaseAttribute('property-base')]
    #[SupportAttrTestChildAttribute('property-child')]
    public $property;

    #[SupportAttrTestBaseAttribute('method-base')]
    #[SupportAttrTestChildAttribute('method-child')]
    public function method(): void
    {
    }
}

class SupportAttrTestChildTargetWithoutAttributes extends SupportAttrTestParentTarget
{
    public $property;

    public function method(): void
    {
    }
}
