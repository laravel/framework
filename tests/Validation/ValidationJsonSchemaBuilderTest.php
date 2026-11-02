<?php

namespace Illuminate\Tests\Validation;

use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\JsonSchemaBuilder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

enum JsonSchemaBuilderStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

enum JsonSchemaBuilderPriority: int
{
    case Low = 1;
    case High = 2;
}

class ValidationJsonSchemaBuilderTest extends TestCase
{
    public function testItBuildsAnObjectSchemaFromStringRules()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'title' => 'required|string|min:3|max:120',
            'views' => 'integer|min:0',
            'rating' => 'nullable|numeric|between:0,5|multiple_of:0.5',
            'published' => 'boolean',
        ]);

        $this->assertInstanceOf(ObjectType::class, $schema);

        $this->assertSame([
            'properties' => [
                'title' => ['minLength' => 3, 'maxLength' => 120, 'type' => 'string'],
                'views' => ['minimum' => 0, 'type' => 'integer'],
                'rating' => ['minimum' => 0, 'maximum' => 5, 'multipleOf' => 0.5, 'type' => ['number', 'null']],
                'published' => ['type' => 'boolean'],
            ],
            'type' => 'object',
            'required' => ['title'],
        ], $schema->toArray());
    }

    public function testItAcceptsRuleBuildersAndRuleObjects()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'name' => ['required', Rule::string()->min(2)->max(50)],
            'age' => Rule::numeric()->integer()->min(18),
            'tags' => Rule::array()->max(5),
            'status' => ['required', Rule::in(['draft', 'published'])],
            'email' => Rule::email(),
            'website' => ['nullable', Rule::url()],
        ]);

        $this->assertSame([
            'properties' => [
                'name' => ['minLength' => 2, 'maxLength' => 50, 'type' => 'string'],
                'age' => ['minimum' => 18, 'type' => 'integer'],
                'tags' => ['maxItems' => 5, 'type' => 'array'],
                'status' => ['enum' => ['draft', 'published'], 'type' => 'string'],
                'email' => ['format' => 'email', 'type' => 'string'],
                'website' => ['format' => 'uri', 'type' => ['string', 'null']],
            ],
            'type' => 'object',
            'required' => ['name', 'status'],
        ], $schema->toArray());
    }

    public function testItMapsEnumRulesToEnumValues()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'status' => Rule::enum(JsonSchemaBuilderStatus::class),
            'priority' => ['integer', Rule::enum(JsonSchemaBuilderPriority::class)],
            'limited' => Rule::enum(JsonSchemaBuilderStatus::class)->only(JsonSchemaBuilderStatus::Draft),
        ]);

        $this->assertSame([
            'status' => ['enum' => ['draft', 'published'], 'type' => 'string'],
            'priority' => ['enum' => [1, 2], 'type' => 'integer'],
            'limited' => ['enum' => ['draft'], 'type' => 'string'],
        ], $schema->toArray()['properties']);
    }

    public function testItNestsDottedAttributesIntoObjects()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'author' => 'required|array',
            'author.name' => 'required|string|max:50',
            'author.email' => 'required|email',
            'author.address.city' => 'string',
        ]);

        $this->assertSame([
            'properties' => [
                'author' => [
                    'properties' => [
                        'name' => ['maxLength' => 50, 'type' => 'string'],
                        'email' => ['format' => 'email', 'type' => 'string'],
                        'address' => [
                            'properties' => [
                                'city' => ['type' => 'string'],
                            ],
                            'type' => 'object',
                        ],
                    ],
                    'type' => 'object',
                    'required' => ['name', 'email'],
                ],
            ],
            'type' => 'object',
            'required' => ['author'],
        ], $schema->toArray());
    }

    public function testItMapsWildcardAttributesToArrayItems()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'tags' => 'array|min:1|max:5',
            'tags.*' => 'string|distinct|max:20',
            'attachments.*.url' => 'required|url',
            'attachments.*.size' => 'integer',
            'matrix.*.*' => 'numeric',
        ]);

        $this->assertSame([
            'tags' => [
                'minItems' => 1,
                'maxItems' => 5,
                'items' => ['maxLength' => 20, 'type' => 'string'],
                'uniqueItems' => true,
                'type' => 'array',
            ],
            'attachments' => [
                'items' => [
                    'properties' => [
                        'url' => ['format' => 'uri', 'type' => 'string'],
                        'size' => ['type' => 'integer'],
                    ],
                    'type' => 'object',
                    'required' => ['url'],
                ],
                'type' => 'array',
            ],
            'matrix' => [
                'items' => [
                    'items' => ['type' => 'number'],
                    'type' => 'array',
                ],
                'type' => 'array',
            ],
        ], $schema->toArray()['properties']);
    }

    public function testItSizesBoundsInTheUnitOfTheType()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'name' => 'string|size:8',
            'count' => 'integer|between:1.5,9.5',
            'items' => 'array|size:3',
            'ratio' => 'decimal:0,2|max:1.5',
        ]);

        $this->assertSame([
            'name' => ['minLength' => 8, 'maxLength' => 8, 'type' => 'string'],
            'count' => ['minimum' => 2, 'maximum' => 9, 'type' => 'integer'],
            'items' => ['minItems' => 3, 'maxItems' => 3, 'type' => 'array'],
            'ratio' => ['maximum' => 1.5, 'type' => 'number'],
        ], $schema->toArray()['properties']);
    }

    public function testItStripsDelimitersFromRegexPatterns()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'slug' => ['regex:/^[a-z0-9-]+$/i'],
            'code' => [Rule::regex('{^[A-Z]{3}$}')],
            'mixed' => ['regex:/^(a|b)$/'],
        ]);

        $this->assertSame([
            'slug' => ['pattern' => '^[a-z0-9-]+$', 'type' => 'string'],
            'code' => ['pattern' => '^[A-Z]{3}$', 'type' => 'string'],
            'mixed' => ['pattern' => '^(a|b)$', 'type' => 'string'],
        ], $schema->toArray()['properties']);
    }

    public function testItMapsDateRulesToFormats()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'published_at' => 'date',
            'birthday' => 'date_format:Y-m-d',
            'uuid' => 'uuid',
            'ip' => 'ipv4',
        ]);

        $this->assertSame([
            'published_at' => ['format' => 'date-time', 'type' => 'string'],
            'birthday' => ['format' => 'date', 'type' => 'string'],
            'uuid' => ['format' => 'uuid', 'type' => 'string'],
            'ip' => ['format' => 'ipv4', 'type' => 'string'],
        ], $schema->toArray()['properties']);
    }

    public function testItIgnoresRulesWithoutASchemaEquivalent()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'email' => ['required', 'email', 'unique:users,email', Rule::exists('users', 'email')],
            'password' => 'required|string|confirmed|min:8',
            'token' => [fn ($attribute, $value, $fail) => $fail('Nope.'), 'string'],
            'role' => ['required_if:type,admin', 'string'],
            'items' => [Rule::forEach(fn () => ['string']), 'array'],
        ]);

        $this->assertSame([
            'properties' => [
                'email' => ['format' => 'email', 'type' => 'string'],
                'password' => ['minLength' => 8, 'type' => 'string'],
                'token' => ['type' => 'string'],
                'role' => ['type' => 'string'],
                'items' => ['type' => 'array'],
            ],
            'type' => 'object',
            'required' => ['email', 'password'],
        ], $schema->toArray());
    }

    public function testItTreatsUntypedAttributesAsStrings()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'anything' => 'required',
            'short' => 'max:10',
        ]);

        $this->assertSame([
            'anything' => ['type' => 'string'],
            'short' => ['maxLength' => 10, 'type' => 'string'],
        ], $schema->toArray()['properties']);
    }

    public function testItTreatsKeyedArrayRulesAsObjects()
    {
        $schema = JsonSchemaBuilder::fromRules([
            'options' => Rule::array(['color', 'size']),
        ]);

        $this->assertSame([
            'options' => [
                'properties' => [
                    'color' => ['type' => 'string'],
                    'size' => ['type' => 'string'],
                ],
                'type' => 'object',
            ],
        ], $schema->toArray()['properties']);
    }

    public function testItReturnsTypesThatCanBeExtended()
    {
        $schema = JsonSchemaBuilder::fromRules(['tags.*' => 'string']);

        $this->assertInstanceOf(ArrayType::class, $schema->properties()['tags']);
        $this->assertSame(['items' => ['type' => 'string'], 'type' => 'array'], $schema->properties()['tags']->toArray());
    }

    public function testItIsAvailableOnTheValidator()
    {
        $translator = new Translator(new ArrayLoader, 'en');

        $validator = new Validator($translator, [], [
            'title' => 'required|string',
            'tags' => 'array',
            'tags.*' => 'string|max:20',
        ]);

        $this->assertSame([
            'properties' => [
                'title' => ['type' => 'string'],
                'tags' => ['items' => ['maxLength' => 20, 'type' => 'string'], 'type' => 'array'],
            ],
            'type' => 'object',
            'required' => ['title'],
        ], $validator->toJsonSchema()->toArray());
    }

    public function testTheSchemaAcceptsWhatTheValidatorAccepts()
    {
        $rules = [
            'title' => 'required|string|min:3|max:120',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'rating' => 'nullable|numeric|between:0,5',
            'tags' => 'array|max:3',
            'tags.*' => 'string|distinct',
            'author.name' => 'required|string',
        ];

        $payload = [
            'title' => 'Hello world',
            'status' => 'draft',
            'rating' => null,
            'tags' => ['php', 'laravel'],
            'author' => ['name' => 'Taylor'],
        ];

        $validator = new Validator(new Translator(new ArrayLoader, 'en'), $payload, $rules);

        $this->assertTrue($validator->passes());

        $schema = JsonSchemaBuilder::fromRules($rules)->toArray();

        $this->assertSame(['title', 'status', 'rating', 'tags', 'author'], array_keys($schema['properties']));
        $this->assertSame(['title', 'status'], $schema['required']);
        $this->assertSame(['name'], $schema['properties']['author']['required']);
    }
}
