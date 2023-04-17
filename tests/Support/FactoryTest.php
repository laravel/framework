<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\CrossJoinSequence;
use Illuminate\Support\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function test_basic_item_can_be_made()
    {
        $requestDetails = SignupRequestFactory::new()->make();
        $this->assertSame(
            ['name' => 'Luke Downing', 'age' => 26, 'country' => 'UK'],
            $requestDetails
        );

        $requestDetails = SignupRequestFactory::new()->makeOne();
        $this->assertSame(
            ['name' => 'Luke Downing', 'age' => 26, 'country' => 'UK'],
            $requestDetails
        );

        $requestDetails = SignupRequestFactory::new()->make(['name' => 'Taylor Otwell']);
        $this->assertSame(
            ['name' => 'Taylor Otwell', 'age' => 26, 'country' => 'UK'],
            $requestDetails
        );

        $requestDetails = SignupRequestFactory::new()->set('name', 'Taylor Otwell')->make();
        $this->assertSame(
            ['name' => 'Taylor Otwell', 'age' => 26, 'country' => 'UK'],
            $requestDetails
        );

        $requestDetails = SignupRequestFactory::times(3)->make();
        $this->assertCount(3, $requestDetails);
    }

    public function test_expanded_closure_attributes_are_resolved_and_passed_to_closures()
    {
        $requestDetails = SignupRequestFactory::new()->make([
            'name' => function () {
                return 'taylor';
            },
            'email' => function ($attributes) {
                return $attributes['name'].'@laravel.com';
            },
        ]);

        $this->assertSame('taylor@laravel.com', $requestDetails['email']);
    }

    public function test_expanded_closure_attribute_returning_a_factory_is_resolved()
    {
        $post = SignupRequestFactory::new()->make([
            'name' => 'Taylor',
            'nested-signup' => fn ($attributes) => SignupRequestFactory::new([
                'name' => 'Freek',
            ]),
        ]);

        $this->assertEquals('Freek', $post['nested-signup']['name']);
    }

    public function test_after_making_callbacks_are_called()
    {
        $requestDetails = SignupRequestFactory::new()
            ->afterMaking(function ($requestDetails) {
                $_SERVER['__test.request_details.making'] = $requestDetails;
            })
            ->make();

        $this->assertSame($requestDetails, $_SERVER['__test.request_details.making']);

        unset($_SERVER['__test.request_details.making']);
    }

    public function test_sequences()
    {
        $requestDetails = SignupRequestFactory::times(2)->sequence(
            ['name' => 'Taylor Otwell'],
            ['name' => 'Nuno Maduro'],
        )->make();

        $this->assertSame('Taylor Otwell', $requestDetails[0]['name']);
        $this->assertSame('Nuno Maduro', $requestDetails[1]['name']);
    }

    public function test_counted_sequence()
    {
        $requestDetails = SignupRequestFactory::new()->forEachSequence(
            ['name' => 'Taylor Otwell'],
            ['name' => 'Nuno Maduro'],
        )->make();

        $this->assertCount(2, $requestDetails);
    }

    public function test_cross_join_sequences()
    {
        $assert = function ($signupDetails) {
            $assertions = [
                ['first_name' => 'Thomas', 'last_name' => 'Anderson'],
                ['first_name' => 'Thomas', 'last_name' => 'Smith'],
                ['first_name' => 'Agent', 'last_name' => 'Anderson'],
                ['first_name' => 'Agent', 'last_name' => 'Smith'],
            ];

            foreach ($assertions as $key => $assertion) {
                $this->assertSame(
                    $assertion,
                    Arr::only($signupDetails[$key], ['first_name', 'last_name']),
                );
            }
        };

        $usersByClass = SignupRequestFactory::times(4)
            ->state(
                new CrossJoinSequence(
                    [['first_name' => 'Thomas'], ['first_name' => 'Agent']],
                    [['last_name' => 'Anderson'], ['last_name' => 'Smith']],
                ),
            )
            ->make();

        $assert($usersByClass);

        $usersByMethod = SignupRequestFactory::times(4)
            ->crossJoinSequence(
                [['first_name' => 'Thomas'], ['first_name' => 'Agent']],
                [['last_name' => 'Anderson'], ['last_name' => 'Smith']],
            )
            ->make();

        $assert($usersByMethod);
    }

    public function test_can_be_macroable()
    {
        $factory = SignupRequestFactory::new();
        $factory->macro('getFoo', function () {
            return 'Hello World';
        });

        $this->assertSame('Hello World', $factory->getFoo());
    }

    public function test_factory_can_conditionally_execute_code()
    {
        SignupRequestFactory::new()
            ->when(true, function () {
                $this->assertTrue(true);
            })
            ->when(false, function () {
                $this->fail('Unreachable code that has somehow been reached.');
            })
            ->unless(false, function () {
                $this->assertTrue(true);
            })
            ->unless(true, function () {
                $this->fail('Unreachable code that has somehow been reached.');
            });
    }

    public function test_attribute_transformers()
    {
        $result = FactoryWithCustomTransformers::new()->make();

        $this->assertSame('BAR', $result['foo']);
    }

    public function test_specialised_build_types()
    {
        [$luke, $owen] = SampleDataTransferObjectFactory::new()
            ->forEachSequence(
                ['name' => 'Luke Downing'],
                ['name' => 'Owen Voke'],
            )
            ->make();

        $this->assertInstanceOf(SampleDataTransferObject::class, $luke);
        $this->assertInstanceOf(SampleDataTransferObject::class, $owen);

        $this->assertSame('Luke Downing', $luke->name->toString());
        $this->assertSame('Owen Voke', $owen->name->toString());
    }

    public function test_index_based_factories()
    {
        $result = IndexBasedFactory::new(['foo'])
            ->state(['bar'])
            ->push('baz')
            ->make(['boom']);

        $this->assertSame(['foo', 'bar', 'baz', 'boom'], $result);
    }
}

/**
 * @extends Factory<array>
 */
class SignupRequestFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => 'Luke Downing',
            'age' => 26,
            'country' => 'UK',
        ];
    }

    protected function makeInstance($expandedAttributes, $parent)
    {
        return $expandedAttributes;
    }
}

class FactoryWithCustomTransformers extends Factory
{
    public function definition()
    {
        return [
            'foo' => Str::of('bar'),
        ];
    }

    protected function expandAttribute($attribute, $key)
    {
        if ($attribute instanceof Stringable) {
            return $attribute->upper()->__toString();
        }

        return parent::expandAttribute($attribute, $key);
    }

    protected function makeInstance($expandedAttributes, $parent)
    {
        return $expandedAttributes;
    }
}

class SampleDataTransferObject
{
    public function __construct(
        public Stringable $name,
        public int $age,
        public string $country,
    ) {
    }
}

class SampleDataTransferObjectFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => 'Luke Downing',
            'age' => 26,
            'country' => 'UK',
        ];
    }

    protected function makeInstance($expandedAttributes, $parent)
    {
        return new SampleDataTransferObject(
            Str::of($expandedAttributes['name']),
            $expandedAttributes['age'],
            $expandedAttributes['country'],
        );
    }
}

class IndexBasedFactory extends Factory
{
    public function definition()
    {
        return [];
    }

    protected function makeInstance($expandedAttributes, $parent)
    {
        return $expandedAttributes;
    }
}
