<?php

// File: tests/Validation/JsonSchemaRuleTest.php

namespace Illuminate\Tests\Validation;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Validation\Rules\JsonSchema as JsonSchemaRule;
use PHPUnit\Framework\TestCase;

class JsonSchemaRuleTest extends TestCase
{
    public function test_passes_with_valid_json_object(): void
    {
        $schema = JsonSchema::object([
            'name' => JsonSchema::string()->required(),
            'age' => JsonSchema::integer()->min(0),
        ]);

        $rule = new JsonSchemaRule($schema);
        $validData = json_encode(['name' => 'John', 'age' => 25]);

        $this->assertTrue($rule->passes('data', $validData));
    }

    public function test_fails_with_missing_required_field(): void
    {
        $schema = JsonSchema::object([
            'name' => JsonSchema::string()->required(),
            'age' => JsonSchema::integer()->min(0),
        ]);

        $rule = new JsonSchemaRule($schema);
        $invalidData = json_encode(['age' => 25]); // missing required 'name'

        $this->assertFalse($rule->passes('data', $invalidData));
    }

    public function test_fails_with_wrong_data_type(): void
    {
        $schema = JsonSchema::object([
            'age' => JsonSchema::integer()->min(0),
        ]);

        $rule = new JsonSchemaRule($schema);
        $invalidData = json_encode(['age' => 'not-a-number']);

        $this->assertFalse($rule->passes('data', $invalidData));
    }

    public function test_works_with_already_decoded_data(): void
    {
        $schema = JsonSchema::object([
            'name' => JsonSchema::string()->required(),
        ]);

        $rule = new JsonSchemaRule($schema);
        $validData = ['name' => 'John']; // already decoded array

        $this->assertTrue($rule->passes('data', $validData));
    }

    public function test_fails_with_invalid_json_string(): void
    {
        $schema = JsonSchema::object([
            'name' => JsonSchema::string()->required(),
        ]);

        $rule = new JsonSchemaRule($schema);
        $invalidJson = '{"name": "John"'; // malformed JSON

        $this->assertFalse($rule->passes('data', $invalidJson));
        $this->assertStringContainsString('Invalid JSON format', $rule->message());
    }

    public function test_handles_json_decode_errors_gracefully(): void
    {
        $schema = JsonSchema::object([
            'name' => JsonSchema::string()->required(),
        ]);

        $rule = new JsonSchemaRule($schema);
        $invalidJson = 'not-json-at-all';

        $this->assertFalse($rule->passes('data', $invalidJson));
        $this->assertStringContainsString('Invalid JSON format', $rule->message());
    }

    public function test_handles_complex_nested_schema(): void
    {
        $schema = JsonSchema::object([
            'user' => JsonSchema::object([
                'profile' => JsonSchema::object([
                    'name' => JsonSchema::string()->required(),
                    'age' => JsonSchema::integer()->min(0)->max(150),
                ])->required(),
                'preferences' => JsonSchema::object([
                    'theme' => JsonSchema::string()->enum(['light', 'dark']),
                    'notifications' => JsonSchema::boolean()->default(true),
                ]),
            ])->required(),
        ]);

        $rule = new JsonSchemaRule($schema);

        // Valid complex data
        $validData = json_encode([
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'age' => 30,
                ],
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
        ]);

        $this->assertTrue($rule->passes('data', $validData));

        // Invalid complex data (missing required field)
        $invalidData = json_encode([
            'user' => [
                'profile' => [
                    'age' => 30, // missing required 'name'
                ],
            ],
        ]);

        $this->assertFalse($rule->passes('data', $invalidData));
    }

    public function test_validates_array_schemas(): void
    {
        $schema = JsonSchema::object([
            'tags' => JsonSchema::array()->items(JsonSchema::string())->min(1)->max(5),
        ]);

        $rule = new JsonSchemaRule($schema);

        // Valid array data
        $validData = json_encode(['tags' => ['php', 'laravel', 'json']]);
        $this->assertTrue($rule->passes('data', $validData));

        // Invalid array data (too many items)
        $invalidData = json_encode(['tags' => ['a', 'b', 'c', 'd', 'e', 'f']]);
        $this->assertFalse($rule->passes('data', $invalidData));

        // Invalid array data (empty array)
        $emptyData = json_encode(['tags' => []]);
        $this->assertFalse($rule->passes('data', $emptyData));
    }

    public function test_validates_enum_values(): void
    {
        $schema = JsonSchema::object([
            'status' => JsonSchema::string()->enum(['draft', 'published', 'archived']),
        ]);

        $rule = new JsonSchemaRule($schema);

        // Valid enum value
        $validData = json_encode(['status' => 'published']);
        $this->assertTrue($rule->passes('data', $validData));

        // Invalid enum value
        $invalidData = json_encode(['status' => 'invalid-status']);
        $this->assertFalse($rule->passes('data', $invalidData));
    }
}
