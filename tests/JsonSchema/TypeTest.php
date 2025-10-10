<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator;
use Opis\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;

class TypeTest extends TestCase
{
    public function test_as_a_array_representation(): void
    {
        $type = JsonSchema::object([
            'age' => JsonSchema::integer()->min(0)->required(),
        ])->title('User')->description('User payload')->default(['age' => 20]);

        $this->assertEquals([
            'type' => 'object',
            'title' => 'User',
            'description' => 'User payload',
            'default' => ['age' => 20],
            'properties' => [
                'age' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
            ],
            'required' => ['age'],
        ], $type->toArray());
    }

    public function test_does_have_a_string_representation(): void
    {
        $type = JsonSchema::object([
            'age' => JsonSchema::integer()->min(0)->required(),
        ])->title('User');

        $this->assertSame(<<<'JSON'
        {
            "title": "User",
            "properties": {
                "age": {
                    "minimum": 0,
                    "type": "integer"
                }
            },
            "type": "object",
            "required": [
                "age"
            ]
        }
        JSON, $type->toString());
    }

    public function test_does_have_a_stringable_representation(): void
    {
        $type = JsonSchema::object([
            'age' => JsonSchema::integer()->min(0)->required(),
        ])->description('Payload');

        $this->assertSame(<<<'JSON'
        {
            "description": "Payload",
            "properties": {
                "age": {
                    "minimum": 0,
                    "type": "integer"
                }
            },
            "type": "object",
            "required": [
                "age"
            ]
        }
        JSON, (string) $type);
    }

    #[DataProvider('validSchemasProvider')]
    public function test_produces_valid_json_schemas(Stringable $schema, mixed $data): void
    {
        $this->assertValidOnJsonSchema($schema, $data);
    }

    #[DataProvider('invalidSchemasProvider')]
    public function test_produces_invalid_json_schemas(Stringable $schema, mixed $data): void
    {
        $this->assertNotValidOnJsonSchema($schema, $data);
    }

    public function test_types_in_object_schema(): void
    {
        $schema = JsonSchema::object(fn (JsonSchema $schema): array => [
            'name' => $schema->string()->required(),
            'age' => $schema->integer()->min(0),
        ]);

        $this->assertInstanceOf(JsonSchema::class, $schema);
    }

    public static function validSchemasProvider(): array
    {
        return [
            // StringType
            [JsonSchema::string(), 'hello'],
            [JsonSchema::string()->min(2), 'hi'],
            [JsonSchema::string()->max(5), 'hello'],
            [JsonSchema::string()->pattern('^foo.*$'), 'foobar'],
            [JsonSchema::string()->default('x'), 'x'],
            [JsonSchema::string()->enum(['draft', 'published']), 'draft'],
            // additional StringType cases
            [JsonSchema::string()->min(0), ''], // empty allowed with min 0
            [JsonSchema::string()->max(0), ''], // exactly zero length
            [JsonSchema::string()->min(1)->max(3), 'a'], // boundary at min
            [JsonSchema::string()->pattern('^[A-Z]{2}[0-9]{2}$'), 'AB12'], // complex pattern
            [JsonSchema::string()->enum(['', 'x', 'y']), ''], // enum including empty string
            [JsonSchema::string()->nullable(), null],
            [JsonSchema::string()->nullable(false), ''],

            // IntegerType
            [JsonSchema::integer(), 10],
            [JsonSchema::integer()->min(0), 0],
            [JsonSchema::integer()->max(120), 120],
            [JsonSchema::integer()->default(18), 18],
            [JsonSchema::integer()->enum([1, 2, 3]), 2],
            // additional IntegerType cases
            [JsonSchema::integer()->min(-5), -5], // negative boundary
            [JsonSchema::integer()->max(10), 9], // below max
            [JsonSchema::integer()->min(1)->max(3), 3], // boundary at max
            [JsonSchema::integer()->enum([0, -1, 5]), 0], // enum with zero
            [JsonSchema::integer()->default(0), 0], // default value
            [JsonSchema::integer()->nullable(), null],
            [JsonSchema::integer()->nullable(false), 0],

            // NumberType
            [JsonSchema::number(), 3.14],
            [JsonSchema::number()->min(0.0), 0.0],
            [JsonSchema::number()->max(100.0), 99.9],
            [JsonSchema::number()->default(9.99), 9.99],
            [JsonSchema::number()->enum([1, 2.5, 3]), 2.5],
            // additional NumberType cases
            [JsonSchema::number()->min(-10.5), -10.5], // negative boundary
            [JsonSchema::number()->min(0)->max(1), 1.0], // boundary at max
            [JsonSchema::number(), 5], // integers are numbers
            [JsonSchema::number()->enum([0.0, 1.1]), 0.0],
            [JsonSchema::number()->default(0.0), 0.0],
            [JsonSchema::number()->nullable(), null],
            [JsonSchema::number()->nullable(false), 0.0],

            // BooleanType
            [JsonSchema::boolean(), true],
            [JsonSchema::boolean()->default(false), false],
            [JsonSchema::boolean()->enum([true, false]), true],
            // additional BooleanType cases
            [JsonSchema::boolean(), false],
            [JsonSchema::boolean()->enum([true, false]), false],
            [JsonSchema::boolean()->enum([true]), true],
            [JsonSchema::boolean()->enum([false]), false],
            [JsonSchema::boolean()->default(true), true],
            [JsonSchema::boolean()->nullable(), null],
            [JsonSchema::boolean()->nullable(false), false],

            // ObjectType
            [
                JsonSchema::object([
                    'name' => JsonSchema::string()->required(),
                    'age' => JsonSchema::integer()->min(21),
                ]),
                (object) ['name' => 'Nuno', 'age' => 30],
            ],
            [
                JsonSchema::object([
                    'meta' => JsonSchema::object()->withoutAdditionalProperties(),
                ]),
                (object) ['meta' => (object) []],
            ],
            [
                JsonSchema::object([
                    'config' => JsonSchema::object()->default(['x' => 1]),
                ]),
                (object) ['config' => (object) ['x' => 1]],
            ],
            [
                JsonSchema::object([
                    'status' => JsonSchema::string()->enum(['draft', 'published']),
                ]),
                (object) ['status' => 'published'],
            ],
            // additional ObjectType cases
            [
                JsonSchema::object([]),
                (object) [], // empty object allowed
            ],
            [
                JsonSchema::object([
                    'name' => JsonSchema::string()->required(),
                ]),
                (object) ['name' => 'John'], // only required present
            ],
            [
                JsonSchema::object([
                    'meta' => JsonSchema::object()->withoutAdditionalProperties(),
                ]),
                (object) [], // optional property omitted
            ],
            [
                JsonSchema::object([
                    'name' => JsonSchema::string(),
                ]),
                (object) ['name' => 'Jane', 'extra' => 1], // additional properties allowed by default
            ],
            [
                JsonSchema::object([
                    'age' => JsonSchema::integer()->min(0),
                ]),
                (object) ['age' => 0], // boundary at min
            ],
            [
                JsonSchema::object([
                    'age' => JsonSchema::integer()->nullable(),
                ]),
                (object) ['age' => null], // nullable
            ],
            [
                JsonSchema::object([
                    'age' => JsonSchema::integer()->nullable(false),
                ]),
                (object) ['age' => 0], // not nullable
            ],

            // ArrayType
            [JsonSchema::array(), []],
            [JsonSchema::array()->min(1), ['a']],
            [JsonSchema::array()->max(2), ['a', 'b']],
            [JsonSchema::array()->items(JsonSchema::string()->max(3)), ['one', 'two']],
            [JsonSchema::array()->default(['x']), ['x']],
            [JsonSchema::array()->enum([['a'], ['b', 'c']]), ['b', 'c']],
            // additional ArrayType cases
            [JsonSchema::array()->min(0), []], // explicit min zero
            [JsonSchema::array()->max(0), []], // exactly zero length
            [JsonSchema::array()->items(JsonSchema::string())->min(2)->max(2), ['a', 'b']],
            [JsonSchema::array()->items(JsonSchema::integer()->min(0)), [0, 1, 2]],
            [JsonSchema::array()->enum([[]]), []],
            [JsonSchema::array()->nullable(), null],
            [JsonSchema::array()->nullable(false), []],
        ];
    }

    public static function invalidSchemasProvider(): array
    {
        return [
            // StringType
            [JsonSchema::string(), 123], // type mismatch
            [JsonSchema::string()->min(3), 'hi'], // too short
            [JsonSchema::string()->max(2), 'long'], // too long
            [JsonSchema::string()->pattern('^foo.*$'), 'barbaz'], // pattern mismatch
            [JsonSchema::string()->default('x'), 10], // default doesn't enforce, but data wrong type
            [JsonSchema::string()->enum(['draft', 'published']), 'archived'], // not in enum
            // additional StringType cases
            [JsonSchema::string()->min(1), ''], // too short (empty)
            [JsonSchema::string()->max(0), 'a'], // too long for zero max
            [JsonSchema::string()->pattern('^[a]+$'), 'ab'], // pattern mismatch
            [JsonSchema::string()->enum(['a', 'b']), 'A'], // case sensitive mismatch
            [JsonSchema::string(), null], // null not allowed
            [JsonSchema::string()->nullable(false), null], // not nullable

            // IntegerType
            [JsonSchema::integer(), '10'], // type mismatch
            [JsonSchema::integer()->min(5), 4], // below min
            [JsonSchema::integer()->max(5), 6], // above max
            [JsonSchema::integer()->default(1), '1'], // wrong type
            [JsonSchema::integer()->enum([1, 2, 3]), 4], // not in enum
            // additional IntegerType cases
            [JsonSchema::integer()->min(0), -1], // below min boundary
            [JsonSchema::integer()->max(0), 1], // above max boundary
            [JsonSchema::integer(), 3.14], // not an integer
            [JsonSchema::integer()->enum([1, 2]), 2.5], // not in enum and not an integer
            [JsonSchema::integer()->default(1), null], // wrong type
            [JsonSchema::integer()->nullable(false), null], // not nullable

            // NumberType
            [JsonSchema::number(), '3.14'], // type mismatch
            [JsonSchema::number()->min(0.5), 0.4], // below min
            [JsonSchema::number()->max(1.5), 1.6], // above max
            [JsonSchema::number()->default(1.1), '1.1'], // wrong type
            [JsonSchema::number()->enum([1, 2.5, 3]), 4], // not in enum
            // additional NumberType cases
            [JsonSchema::number()->min(0), -0.0001], // below min
            [JsonSchema::number()->max(10), 10.0001], // above max
            [JsonSchema::number(), 'NaN'], // string, not number
            [JsonSchema::number()->enum([1.1, 2.2]), 1.1000001], // not exactly in enum
            [JsonSchema::number()->default(0.0), []], // wrong type
            [JsonSchema::number()->nullable(false), null], // not nullable

            // BooleanType
            [JsonSchema::boolean(), 'true'], // type mismatch
            [JsonSchema::boolean()->default(false), 0], // wrong type
            [JsonSchema::boolean()->enum([true, false]), null], // not in enum
            // additional BooleanType cases
            [JsonSchema::boolean()->enum([true]), false], // not in enum
            [JsonSchema::boolean()->enum([false]), true], // not in enum
            [JsonSchema::boolean(), 1], // wrong type
            [JsonSchema::boolean()->default(true), 'true'], // wrong type
            [JsonSchema::boolean(), null], // null is invalid
            [JsonSchema::boolean()->nullable(false), null], // not nullable

            // ObjectType
            [JsonSchema::object(['name' => JsonSchema::string()->required()]), (object) []], // missing required
            [JsonSchema::object(['meta' => JsonSchema::object()->withoutAdditionalProperties()]), (object) ['meta' => (object) ['x' => 1]]], // additional prop not allowed
            [JsonSchema::object(['age' => JsonSchema::integer()->min(21)]), (object) ['age' => 18]], // below min in nested schema
            // additional ObjectType cases
            [JsonSchema::object([])->withoutAdditionalProperties(), (object) ['x' => 1]], // no additional properties allowed
            [JsonSchema::object(['name' => JsonSchema::string()]), (object) ['name' => 123]], // wrong type for property
            [JsonSchema::object(['meta' => JsonSchema::object()->withoutAdditionalProperties()]), (object) ['meta' => 'nope']], // wrong type in nested object
            [JsonSchema::object(['name' => JsonSchema::string()->required()])->default(['name' => 'x']), (object) []], // default doesn't satisfy missing required
            [JsonSchema::object(['score' => JsonSchema::integer()->min(0)]), (object) ['score' => -1]], // below min
            [JsonSchema::object(['age' => JsonSchema::integer()->nullable(false)]), (object) ['age' => null]], // not nullable

            // ArrayType
            [JsonSchema::array(), (object) []], // type mismatch
            [JsonSchema::array()->min(2), ['a']], // too few items
            [JsonSchema::array()->max(1), ['a', 'b']], // too many items
            [JsonSchema::array()->items(JsonSchema::string()->max(3)), ['four']], // item too long
            [JsonSchema::array()->enum([['a'], ['b', 'c']]), ['c', 'd']], // not in enum
            // additional ArrayType cases
            [JsonSchema::array()->items(JsonSchema::integer()), ['a']], // wrong item type
            [JsonSchema::array()->min(1), []], // too few
            [JsonSchema::array()->max(0), ['a']], // too many for zero max
            [JsonSchema::array()->enum([['a'], ['b']]), ['a', 'b']], // not equal to any enum member
            [JsonSchema::array()->items(JsonSchema::string()->max(1)), ['ab']], // item too long
            [JsonSchema::array()->nullable(false), null], // not nullable
        ];
    }

    protected function makeValidator(): Validator
    {
        $loader = new SchemaLoader;
        $resolver = new SchemaResolver;

        $loader->setResolver($resolver);
        $resolver->registerProtocol('https', function (Uri $uri) {
            return file_get_contents($uri->__toString());
        });

        return new Validator($loader);
    }

    protected function assertValidOnJsonSchema(Stringable $schema, mixed $data): void
    {
        $validator = $this->makeValidator();
        $result = $validator->validate($data, (string) $schema);
        $errorMessage = $result->error()?->message() ?? 'The JSON schema is valid.';
        $this->assertTrue($result->isValid(), $errorMessage);
    }

    protected function assertNotValidOnJsonSchema(Stringable $schema, mixed $data): void
    {
        $validator = $this->makeValidator();
        $result = $validator->validate($data, (string) $schema);
        $this->assertFalse($result->isValid());
    }
}
