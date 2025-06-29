<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rules\IsObject;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

include_once 'Classes.php';

class ValidationIsObjectRuleTest extends TestCase
{
    public function test_validation_passes_when_passing_correct_class()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => new Foo,
            ],
            [
                'object' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_parent_class()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => new Bar,
            ],
            [
                'object' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_interface()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => new FooImplementation,
            ],
            [
                'object' => new IsObject(FooInterface::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_iterable_containing_correct_classes()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Foo, new Foo, new Foo],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_iterable_containing_child_classes()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Bar, new Bar, new Foo],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_iterable_containing_null_and_correct_classes_in_non_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Bar, null, new Foo],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_traversable_containing_correct_classes()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => new Collection([new Foo, new Foo, new Foo]),
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_traversable_containing_child_classes()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => new Collection([new Bar, new Bar, new Foo]),
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_traversable_containing_null_and_correct_classes_in_non_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => new Collection([new Bar, null, new Foo]),
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_empty_iterable()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_validation_fails_with_empty_traversable()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => new Collection([]),
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_validation_fails_when_providing_not_existing_class()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => new Foo,
            ],
            [
                'object' => new IsObject('Illuminate\Tests\Validation\Baz'),
            ]
        );

        $this->assertTrue($validator->fails());

        $this->assertEquals(['The field object is not a valid instance.'], $validator->messages()->get('object'));
    }

    public function test_validation_fails_when_providing_different_class()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => new Foo,
            ],
            [
                'object' => new IsObject(Bar::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field object is not a valid instance.'], $validator->messages()->get('object'));
    }

    public function test_validation_fails_when_value_is_not_an_object()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => 'object',
            ],
            [
                'object' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field object is not a valid instance.'], $validator->messages()->get('object'));
    }

    public function test_validation_fails_when_value_is_null()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'object' => null,
            ],
            [
                'object' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field object is not a valid instance.'], $validator->messages()->get('object'));
    }

    public function test_validation_fails_with_iterable_containing_only_null_in_non_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [null],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_iterable_containing_only_null_in_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [null],
            ],
            [
                'objects' => (new IsObject(Foo::class))->strict(),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_iterable_containing_null_and_correct_classes_in_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Bar, null, new Foo],
            ],
            [
                'objects' => (new IsObject(Foo::class))->strict(),
            ]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_validation_fails_with_iterable_containing_wrong_classes()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Foo, new FooImplementation, new Foo],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_iterable_containing_non_objects()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Foo, 'string', 123],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_traversable_containing_only_null_in_non_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => new Collection([null]),
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_traversable_containing_only_null_in_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [null],
            ],
            [
                'objects' => (new IsObject(Foo::class))->strict(),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_traversable_containing_null_and_correct_classes_in_strict_mode()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Bar, null, new Foo],
            ],
            [
                'objects' => (new IsObject(Foo::class))->strict(),
            ]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_validation_fails_with_traversable_containing_wrong_classes()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Foo, new FooImplementation, new Foo],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    public function test_validation_fails_with_traversable_containing_non_objects()
    {
        $validator = new Validator(
            resolve('translator'),
            [
                'objects' => [new Foo, 'string', 123],
            ],
            [
                'objects' => new IsObject(Foo::class),
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(['The field objects contains invalid instances.'], $validator->messages()->get('objects'));
    }

    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }
}
