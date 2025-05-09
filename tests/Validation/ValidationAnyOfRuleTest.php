<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

enum TaggedUnionDiscriminatorType: string
{
    case EMAIL = 'email';
    case URL = 'url';
}

class ValidationAnyOfRuleTest extends TestCase
{
    private array $taggedUnionRules;
    private array $dotNotationNestedRules;
    private array $nestedRules;

    public function testBasicValidation()
    {
        $rule = Rule::anyOf([
            ['required', 'uuid:4'],
            ['required', 'email'],
        ]);
        $idRule = ['id' => $rule];
        $requiredIdRule = ['id' => ['required', $rule]];

        $validator = new Validator(resolve('translator'), [
            'id' => 'taylor@laravel.com',
        ], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [], $requiredIdRule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => '3c8ff5cb-4bc1-457b-a477-1833c477b254',
        ], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => null,
        ], $idRule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => '',
        ], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => '',
        ], $requiredIdRule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => 'abc',
        ], $idRule);
        $this->assertFalse($validator->passes());
    }

    public function testBasicStringValidation()
    {
        $rule = Rule::anyOf([
            'required|uuid:4',
            'required|email',
        ]);
        $idRule = ['id' => $rule];
        $requiredIdRule = ['id' => ['required', $rule]];

        $validator = new Validator(resolve('translator'), [
            'id' => 'test@example.com',
        ], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [], $requiredIdRule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => '3c8ff5cb-4bc1-457b-a477-1833c477b254',
        ], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => null,
        ], $idRule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => '',
        ], $idRule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => '',
        ], $requiredIdRule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'id' => 'abc',
        ], $idRule);
        $this->assertFalse($validator->passes());
    }

    public function testTaggedUnionObjects()
    {
        $validator = new Validator(resolve('translator'), [
            'data' => [
                'type' => TaggedUnionDiscriminatorType::EMAIL->value,
                'email' => 'taylor@laravel.com',
            ],
        ], ['data' => Rule::anyOf($this->taggedUnionRules)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'data' => [
                'type' => TaggedUnionDiscriminatorType::EMAIL->value,
                'email' => 'invalid-email',
            ],
        ], ['data' => Rule::anyOf($this->taggedUnionRules)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'data' => [
                'type' => TaggedUnionDiscriminatorType::URL->value,
                'url' => 'http://laravel.com',
            ],
        ], ['data' => Rule::anyOf($this->taggedUnionRules)]);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'data' => [
                'type' => TaggedUnionDiscriminatorType::URL->value,
                'url' => 'not-a-url',
            ],
        ], ['data' => Rule::anyOf($this->taggedUnionRules)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'data' => [
                'type' => TaggedUnionDiscriminatorType::EMAIL->value,
                'url' => 'url-should-not-be-present-with-email-discriminator',
            ],
        ], ['data' => Rule::anyOf($this->taggedUnionRules)]);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'data' => [
                'type' => 'doesnt-exist',
                'email' => 'taylor@laravel.com',
            ],
        ], ['data' => Rule::anyOf($this->taggedUnionRules)]);
        $this->assertFalse($validator->passes());
    }

    public function testNestedValidation()
    {
        $validator = new Validator(resolve('translator'), [
            'user' => [
                'identifier' => 1,
                'properties' => [
                    'name' => 'Taylor',
                    'surname' => 'Otwell',
                ],
            ],
        ], $this->nestedRules);
        $this->assertTrue($validator->passes());
        $validator->setRules($this->dotNotationNestedRules);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'user' => [
                'identifier' => 'taylor@laravel.com',
                'properties' => [
                    'bio' => 'biography',
                    'name' => 'Taylor',
                    'surname' => 'Otwell',
                ],
            ],
        ], $this->nestedRules);
        $this->assertTrue($validator->passes());
        $validator->setRules($this->dotNotationNestedRules);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'user' => [
                'identifier' => 'taylor@laravel.com',
                'properties' => [
                    'name' => null,
                    'surname' => 'Otwell',
                ],
            ],
        ], $this->nestedRules);
        $this->assertFalse($validator->passes());
        $validator->setRules($this->dotNotationNestedRules);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'user' => [
                'properties' => [
                    'name' => 'Taylor',
                    'surname' => 'Otwell',
                ],
            ],
        ], $this->nestedRules);
        $this->assertFalse($validator->passes());
        $validator->setRules($this->dotNotationNestedRules);
        $this->assertFalse($validator->passes());
    }

    public function testStarRuleSimple()
    {
        $rule = [
            'persons.*.age' => ['required', Rule::anyOf([
                ['min:10'],
                ['integer'],
            ])],
        ];

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['age' => 12],
                ['age' => 'foobar'],
            ],
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['age' => 'foobarbazqux'],
                ['month' => 12],
            ],
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['age' => 12],
                ['age' => 'foobarbazqux'],
            ],
        ], $rule);
        $this->assertTrue($validator->passes());
    }

    public function testStarRuleNested()
    {
        $rule = [
            'persons.*.birth' => ['required', Rule::anyOf([
                ['year' => 'required|integer'],
                'required|min:10',
            ])],
        ];

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['age' => ['year' => 12]],
            ],
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['birth' => ['month' => 12]],
            ],
        ], $rule);
        $this->assertFalse($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['birth' => ['year' => 12]],
            ],
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['birth' => 'foobarbazqux'],
                ['birth' => [
                    'year' => 12,
                ]],
            ],
        ], $rule);
        $this->assertTrue($validator->passes());

        $validator = new Validator(resolve('translator'), [
            'persons' => [
                ['birth' => 'foobar'],
                ['birth' => [
                    'year' => 12,
                ]],
            ],
        ], $rule);
        $this->assertFalse($validator->passes());
    }

    protected function setUpRuleSets()
    {
        $this->taggedUnionRules = [
            [
                'type' => ['required', Rule::in([TaggedUnionDiscriminatorType::EMAIL])],
                'email' => ['required', 'email:rfc'],
            ],
            [
                'type' => ['required', Rule::in([TaggedUnionDiscriminatorType::URL])],
                'url' => ['required', 'url:http,https'],
            ],
        ];

        // Using AnyOf as nesting feature
        $this->nestedRules = [
            'user' => Rule::anyOf([
                [
                    'identifier' => ['required', Rule::anyOf([
                        'email:rfc',
                        'integer',
                    ])],
                    'properties' => ['required', Rule::anyOf([
                        [
                            'bio' => 'nullable',
                            'name' => 'required',
                            'surname' => 'required',
                        ],
                    ])],
                ],
            ]),
        ];

        $this->dotNotationNestedRules = [
            'user.identifier' => ['required', Rule::anyOf([
                'email:rfc',
                'integer',
            ])],
            'user.properties.bio' => 'nullable',
            'user.properties.name' => 'required',
            'user.properties.surname' => 'required',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();
        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader,
                'en'
            );
        });

        Facade::setFacadeApplication($container);
        (new ValidationServiceProvider($container))->register();

        $this->setUpRuleSets();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }
}
