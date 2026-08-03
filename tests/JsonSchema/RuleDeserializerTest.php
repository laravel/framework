<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class RuleDeserializerTest extends TestCase
{
    public function test_it_maps_the_scalar_type_rules(): void
    {
        $type = JsonSchema::fromRules([
            'name' => 'string',
            'age' => 'integer',
            'score' => 'numeric',
            'active' => 'boolean',
            'tags' => 'array',
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
                'score' => ['type' => 'number'],
                'active' => ['type' => 'boolean'],
                'tags' => ['type' => 'array'],
            ],
        ], $type->toArray());
    }

    public function test_it_accepts_rules_given_as_arrays(): void
    {
        $type = JsonSchema::fromRules([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => ['maxLength' => 50, 'type' => 'string'],
            ],
            'required' => ['name'],
        ], $type->toArray());
    }

    public function test_it_marks_required_and_nullable_properties(): void
    {
        $type = JsonSchema::fromRules([
            'name' => 'required|string',
            'nickname' => 'nullable|string',
        ])->toArray();

        $this->assertSame(['name'], $type['required']);
        $this->assertSame(['string', 'null'], $type['properties']['nickname']['type']);
    }

    public function test_it_resolves_size_rules_against_the_resolved_type(): void
    {
        $type = JsonSchema::fromRules([
            'name' => 'string|min:2|max:50',
            'age' => 'integer|min:0|max:120',
            'score' => 'numeric|min:0.5',
            'tags' => 'array|min:1|max:5',
        ]);

        $this->assertEquals([
            'name' => ['minLength' => 2, 'maxLength' => 50, 'type' => 'string'],
            'age' => ['minimum' => 0, 'maximum' => 120, 'type' => 'integer'],
            'score' => ['minimum' => 0.5, 'type' => 'number'],
            'tags' => ['minItems' => 1, 'maxItems' => 5, 'type' => 'array'],
        ], $type->toArray()['properties']);
    }

    public function test_it_treats_an_untyped_field_as_a_string(): void
    {
        $type = JsonSchema::fromRules(['name' => 'required|max:10']);

        $this->assertEquals(
            ['maxLength' => 10, 'type' => 'string'],
            $type->toArray()['properties']['name']
        );
    }

    public function test_it_maps_between_and_size_rules(): void
    {
        $type = JsonSchema::fromRules([
            'age' => 'integer|between:18,65',
            'code' => 'string|size:6',
            'tags' => 'array|size:3',
        ]);

        $this->assertEquals([
            'age' => ['minimum' => 18, 'maximum' => 65, 'type' => 'integer'],
            'code' => ['minLength' => 6, 'maxLength' => 6, 'type' => 'string'],
            'tags' => ['minItems' => 3, 'maxItems' => 3, 'type' => 'array'],
        ], $type->toArray()['properties']);
    }

    public function test_it_maps_the_in_rule_to_an_enum(): void
    {
        $type = JsonSchema::fromRules(['role' => 'in:admin,editor,viewer']);

        $this->assertSame(
            ['admin', 'editor', 'viewer'],
            $type->toArray()['properties']['role']['enum']
        );
    }

    public function test_it_maps_string_formats_and_patterns(): void
    {
        $type = JsonSchema::fromRules([
            'email' => 'email',
            'website' => 'url',
            'identifier' => 'uuid',
            'born' => 'date',
            'host' => 'ipv4',
            'slug' => 'regex:/^[a-z]+$/',
        ]);

        $this->assertEquals([
            'email' => ['format' => 'email', 'type' => 'string'],
            'website' => ['format' => 'uri', 'type' => 'string'],
            'identifier' => ['format' => 'uuid', 'type' => 'string'],
            'born' => ['format' => 'date', 'type' => 'string'],
            'host' => ['format' => 'ipv4', 'type' => 'string'],
            'slug' => ['pattern' => '^[a-z]+$', 'type' => 'string'],
        ], $type->toArray()['properties']);
    }

    public function test_it_maps_the_multiple_of_rule(): void
    {
        $type = JsonSchema::fromRules([
            'quantity' => 'integer|multiple_of:5',
            'price' => 'numeric|multiple_of:0.25',
        ]);

        $this->assertEquals([
            'quantity' => ['multipleOf' => 5, 'type' => 'integer'],
            'price' => ['multipleOf' => 0.25, 'type' => 'number'],
        ], $type->toArray()['properties']);
    }

    public function test_it_ignores_formats_it_cannot_represent_exactly(): void
    {
        // Laravel's "ip" accepts either version and a ULID is not a UUID, so
        // neither has an exact JSON Schema format to map onto...
        $type = JsonSchema::fromRules(['host' => 'ip', 'identifier' => 'ulid']);

        $this->assertEquals([
            'host' => ['type' => 'string'],
            'identifier' => ['type' => 'string'],
        ], $type->toArray()['properties']);
    }

    public function test_it_nests_properties_written_in_dot_notation(): void
    {
        $type = JsonSchema::fromRules([
            'user' => 'required',
            'user.name' => 'required|string',
            'user.address.city' => 'string',
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'address' => [
                            'type' => 'object',
                            'properties' => ['city' => ['type' => 'string']],
                        ],
                    ],
                    'required' => ['name'],
                ],
            ],
            'required' => ['user'],
        ], $type->toArray());
    }

    public function test_it_maps_wildcard_rules_to_array_items(): void
    {
        $type = JsonSchema::fromRules([
            'tags' => 'required|array|min:1',
            'tags.*' => 'string|max:20',
        ]);

        $this->assertEquals([
            'items' => ['maxLength' => 20, 'type' => 'string'],
            'minItems' => 1,
            'type' => 'array',
        ], $type->toArray()['properties']['tags']);
    }

    public function test_it_maps_wildcard_rules_describing_objects(): void
    {
        $type = JsonSchema::fromRules([
            'users' => 'array',
            'users.*.name' => 'required|string',
            'users.*.age' => 'integer',
        ]);

        $this->assertEquals([
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => ['type' => 'integer'],
                ],
                'required' => ['name'],
            ],
            'type' => 'array',
        ], $type->toArray()['properties']['users']);
    }

    public function test_it_infers_an_array_from_a_wildcard_without_its_own_rules(): void
    {
        $type = JsonSchema::fromRules(['tags.*' => 'string']);

        $this->assertEquals(
            ['items' => ['type' => 'string'], 'type' => 'array'],
            $type->toArray()['properties']['tags']
        );
    }

    public function test_it_ignores_rules_it_cannot_represent(): void
    {
        $type = JsonSchema::fromRules([
            'password' => 'required|string|confirmed|not_in:secret',
            'avatar' => 'image|mimes:jpg,png',
        ]);

        $this->assertEquals([
            'password' => ['type' => 'string'],
            'avatar' => ['type' => 'string'],
        ], $type->toArray()['properties']);
    }

    public function test_it_ignores_rule_objects_and_closures(): void
    {
        $type = JsonSchema::fromRules([
            'name' => ['required', 'string', fn () => true, new \stdClass],
        ]);

        $this->assertEquals(
            ['type' => 'string'],
            $type->toArray()['properties']['name']
        );
    }

    public function test_it_returns_an_empty_object_for_no_rules(): void
    {
        $this->assertSame(['type' => 'object'], JsonSchema::fromRules([])->toArray());
    }
}
