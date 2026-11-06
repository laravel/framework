<?php

namespace Illuminate\Tests\JsonSchema;

use Closure;
use Illuminate\Container\Container;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidationRulesTest extends TestCase
{
    protected function setUp(): void
    {
        $container = Container::getInstance();

        $container->bind('translator', fn () => new Translator(new ArrayLoader, 'en'));

        Facade::setFacadeApplication($container);

        (new ValidationServiceProvider($container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    #[DataProvider('schemaCases')]
    public function test_a_value_passes_or_fails_the_schema_it_was_declared_with(Closure $properties, array $data, bool $passes)
    {
        $this->assertSame($passes, $this->passes($this->strict($properties), $data));
    }

    public static function schemaCases(): array
    {
        return [
            'required present' => [fn ($s) => ['name' => $s->string()->required()], ['name' => 'Taylor'], true],
            'required missing' => [fn ($s) => ['name' => $s->string()->required()], [], false],
            'required null' => [fn ($s) => ['name' => $s->string()->required()], ['name' => null], false],
            'optional missing' => [fn ($s) => ['name' => $s->string()], [], true],
            'nullable null' => [fn ($s) => ['name' => $s->string()->nullable()->required()], ['name' => null], true],
            'string given integer' => [fn ($s) => ['name' => $s->string()->required()], ['name' => 5], false],
            'string too long' => [fn ($s) => ['name' => $s->string()->max(3)->required()], ['name' => 'Taylor'], false],
            'string off pattern' => [fn ($s) => ['code' => $s->string()->pattern('^[A-Z]{3}$')->required()], ['code' => 'ab/'], false],
            'string on pattern' => [fn ($s) => ['code' => $s->string()->pattern('^[A-Z]{3}$')->required()], ['code' => 'LIS'], true],
            'string on an escaped slash pattern' => [fn ($s) => ['path' => $s->string()->pattern('^a\\/b$')->required()], ['path' => 'a/b'], true],
            'date-time in RFC 3339' => [fn ($s) => ['at' => $s->string()->format('date-time')->required()], ['at' => '2026-11-06T10:30:00.250+05:30'], true],
            'date-time in UTC' => [fn ($s) => ['at' => $s->string()->format('date-time')->required()], ['at' => '2026-11-06T10:30:00Z'], true],
            'date-time as a relative phrase' => [fn ($s) => ['at' => $s->string()->format('date-time')->required()], ['at' => 'tomorrow'], false],
            'date-time on an impossible day' => [fn ($s) => ['at' => $s->string()->format('date-time')->required()], ['at' => '2026-02-30T10:30:00Z'], false],
            'email format' => [fn ($s) => ['email' => $s->string()->format('email')->required()], ['email' => 'not-an-email'], false],
            'enum outside' => [fn ($s) => ['plan' => $s->string()->enum(['Basic', 'Pro'])->required()], ['plan' => 'Gold'], false],
            'enum inside' => [fn ($s) => ['plan' => $s->string()->enum(['Basic', 'Pro'])->required()], ['plan' => 'Pro'], true],
            'integer below minimum' => [fn ($s) => ['seats' => $s->integer()->min(1)->required()], ['seats' => 0], false],
            'integer given float' => [fn ($s) => ['seats' => $s->integer()->required()], ['seats' => 1.5], false],
            'integer given numeric string' => [fn ($s) => ['seats' => $s->integer()->required()], ['seats' => '5'], true],
            'number given numeric string' => [fn ($s) => ['ratio' => $s->number()->required()], ['ratio' => '0.5'], true],
            'boolean given string' => [fn ($s) => ['agree' => $s->boolean()->required()], ['agree' => '1'], true],
            'number above maximum' => [fn ($s) => ['ratio' => $s->number()->max(1)->required()], ['ratio' => 1.5], false],
            'boolean false' => [fn ($s) => ['agree' => $s->boolean()->required()], ['agree' => false], true],
            'array too many items' => [fn ($s) => ['tags' => $s->array()->items($s->string())->max(1)->required()], ['tags' => ['a', 'b']], false],
            'array with a bad item' => [fn ($s) => ['tags' => $s->array()->items($s->string()->enum(['a', 'b']))->required()], ['tags' => ['a', 'c']], false],
            'array given an object' => [fn ($s) => ['tags' => $s->array()->items($s->string())->required()], ['tags' => ['x' => 'a']], false],
            'array with duplicates' => [fn ($s) => ['tags' => $s->array()->items($s->string())->unique()->required()], ['tags' => ['a', 'a']], false],
            'object missing a nested key' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->required()], ['address' => []], false],
            'object with an unknown key' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->required()], ['address' => ['city' => 'Lisbon', 'admin' => true]], false],
            'optional empty string' => [fn ($s) => ['note' => $s->string()->enum(['a', 'b'])], ['note' => ''], false],
            'empty string item' => [fn ($s) => ['tags' => $s->array()->items($s->string())->required()], ['tags' => ['']], false],
            'optional nullable null' => [fn ($s) => ['note' => $s->string()->nullable()], ['note' => null], true],
            'optional empty array' => [fn ($s) => ['tags' => $s->array()->items($s->string())], ['tags' => []], true],
            'object valid' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->required()], ['address' => ['city' => 'Lisbon']], true],
            'required empty array' => [fn ($s) => ['tags' => $s->array()->items($s->string())->required()], ['tags' => []], false],
            'required empty object' => [fn ($s) => ['meta' => $s->object()->required()], ['meta' => []], false],
            'empty object given a key' => [fn ($s) => ['meta' => $s->object()->required()], ['meta' => ['admin' => true]], false],
            'optional object missing with a required key' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])], [], true],
            'optional object given with a missing key' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])], ['address' => []], false],
            'nullable object null with a required key' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->nullable()->required()], ['address' => null], true],
            'required nested key given null' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->required()], ['address' => ['city' => null]], false],
            'required nested key given an empty string' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->required()], ['address' => ['city' => '']], false],
            'required nested key given an empty array' => [fn ($s) => ['order' => $s->object(['tags' => $s->array()->items($s->string())->required()])->required()], ['order' => ['tags' => []]], false],
            'nullable nested key given null' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->nullable()->required()])->required()], ['address' => ['city' => null]], true],
            'nullable nested key missing' => [fn ($s) => ['address' => $s->object(['city' => $s->string()->nullable()->required()])->required()], ['address' => ['zip' => '1000']], false],
            'optional array of objects missing a required key' => [fn ($s) => ['people' => $s->array()->items($s->object(['age' => $s->integer()->required()]))], ['people' => [['age' => 1], []]], false],
        ];
    }

    public function test_an_any_of_value_passes_when_any_branch_matches()
    {
        $schema = [
            'type' => 'object',
            'properties' => ['plan' => ['anyOf' => [['type' => 'string', 'enum' => ['Basic', 'Pro']], ['type' => 'integer']]]],
            'required' => ['plan'],
        ];

        $this->assertTrue($this->passes($schema, ['plan' => 'Pro']));
        $this->assertTrue($this->passes($schema, ['plan' => 3]));
        $this->assertFalse($this->passes($schema, ['plan' => 'Gold']));
        $this->assertFalse($this->passes($schema, ['plan' => null]));
    }

    public function test_a_multi_type_union_passes_any_of_its_types()
    {
        $schema = ['type' => 'object', 'properties' => ['id' => ['type' => ['string', 'integer']]], 'required' => ['id']];

        $this->assertTrue($this->passes($schema, ['id' => 'abc']));
        $this->assertTrue($this->passes($schema, ['id' => 7]));
        $this->assertFalse($this->passes($schema, ['id' => ['a']]));
    }

    public function test_property_names_with_a_dot_are_not_treated_as_nested_paths()
    {
        $schema = ['type' => 'object', 'properties' => ['v1.0' => ['type' => 'integer']], 'required' => ['v1.0']];

        $this->assertTrue($this->passes($schema, ['v1.0' => 1]));
        $this->assertFalse($this->passes($schema, ['v1.0' => 'x']));
    }

    public function test_numeric_property_names_keep_their_own_rules()
    {
        $schema = ['type' => 'object', 'properties' => ['1' => ['type' => 'string'], '2' => ['type' => 'integer']], 'required' => ['2']];

        $this->assertFalse($this->passes($schema, [2 => 'x']));
        $this->assertTrue($this->passes($schema, [2 => 7]));
    }

    public function test_a_pattern_with_an_escaped_slash_matches_a_literal_slash()
    {
        $schema = $this->schema(fn ($s) => ['path' => $s->string()->pattern('^a\/b$')->required()]);

        $this->assertTrue($this->passes($schema, ['path' => 'a/b']));
        $this->assertFalse($this->passes($schema, ['path' => 'a\/b']));
    }

    public function test_a_pattern_with_an_escaped_backslash_before_a_slash_matches_a_backslash_and_a_slash()
    {
        $schema = $this->schema(fn ($s) => ['path' => $s->string()->pattern('^a\\\\/b$')->required()]);

        $this->assertTrue($this->passes($schema, ['path' => 'a\\/b']));
        $this->assertFalse($this->passes($schema, ['path' => 'a/b']));
    }

    public function test_a_pattern_with_an_unescaped_slash_matches_a_literal_slash()
    {
        $schema = $this->schema(fn ($s) => ['path' => $s->string()->pattern('^a/b/c$')->required()]);

        $this->assertTrue($this->passes($schema, ['path' => 'a/b/c']));
        $this->assertFalse($this->passes($schema, ['path' => 'a-b-c']));
    }

    public function test_it_accepts_a_type()
    {
        $schema = JsonSchema::object(fn ($s) => ['name' => $s->string()->required()]);

        $this->assertTrue(Validator::make(['name' => 'Taylor'], JsonSchema::rules($schema))->passes());
        $this->assertFalse(Validator::make([], JsonSchema::rules($schema))->passes());
    }

    public function test_it_keys_nested_rules_in_dot_notation()
    {
        $rules = JsonSchema::rules(JsonSchema::object(fn ($s) => [
            'address' => $s->object(['city' => $s->string()]),
            'tags' => $s->array()->items($s->string()),
        ]));

        $this->assertSame(['address', 'address.city', 'tags', 'tags.*'], array_keys($rules));
    }

    public function test_min_length()
    {
        $schema = $this->schema(fn ($s) => ['name' => $s->string()->min(3)->required()]);

        $this->assertTrue($this->passes($schema, ['name' => 'abc']));
        $this->assertFalse($this->passes($schema, ['name' => 'ab']));
    }

    public function test_number_minimum()
    {
        $schema = $this->schema(fn ($s) => ['ratio' => $s->number()->min(0.5)->required()]);

        $this->assertTrue($this->passes($schema, ['ratio' => 0.5]));
        $this->assertFalse($this->passes($schema, ['ratio' => 0.4]));
    }

    public function test_integer_maximum()
    {
        $schema = $this->schema(fn ($s) => ['seats' => $s->integer()->max(10)->required()]);

        $this->assertTrue($this->passes($schema, ['seats' => 10]));
        $this->assertFalse($this->passes($schema, ['seats' => 11]));
    }

    public function test_integer_multiple_of()
    {
        $schema = $this->schema(fn ($s) => ['seats' => $s->integer()->multipleOf(5)->required()]);

        $this->assertTrue($this->passes($schema, ['seats' => 15]));
        $this->assertFalse($this->passes($schema, ['seats' => 12]));
    }

    public function test_number_multiple_of()
    {
        $schema = $this->schema(fn ($s) => ['price' => $s->number()->multipleOf(0.25)->required()]);

        $this->assertTrue($this->passes($schema, ['price' => 1.75]));
        $this->assertFalse($this->passes($schema, ['price' => 1.8]));
    }

    public function test_min_items()
    {
        $schema = $this->schema(fn ($s) => ['tags' => $s->array()->items($s->string())->min(2)->required()]);

        $this->assertTrue($this->passes($schema, ['tags' => ['a', 'b']]));
        $this->assertFalse($this->passes($schema, ['tags' => ['a']]));
    }

    public function test_unique_items_without_duplicates()
    {
        $schema = $this->schema(fn ($s) => ['tags' => $s->array()->items($s->string())->unique()->required()]);

        $this->assertTrue($this->passes($schema, ['tags' => ['a', 'b']]));
    }

    public function test_integer_enum()
    {
        $schema = $this->schema(fn ($s) => ['size' => $s->integer()->enum([1, 2, 3])->required()]);

        $this->assertTrue($this->passes($schema, ['size' => 2]));
        $this->assertFalse($this->passes($schema, ['size' => 4]));
    }

    public function test_uuid_format()
    {
        $schema = $this->schema(fn ($s) => ['id' => $s->string()->format('uuid')->required()]);

        $this->assertTrue($this->passes($schema, ['id' => '3c8ff5cb-4bc1-457b-a477-1833c477b254']));
        $this->assertFalse($this->passes($schema, ['id' => 'not-a-uuid']));
    }

    public function test_uri_and_url_formats()
    {
        foreach (['uri', 'url'] as $format) {
            $schema = $this->schema(fn ($s) => ['link' => $s->string()->format($format)->required()]);

            $this->assertTrue($this->passes($schema, ['link' => 'https://laravel.com']), $format);
            $this->assertFalse($this->passes($schema, ['link' => 'laravel']), $format);
        }
    }

    public function test_email_format_with_a_valid_address()
    {
        $schema = $this->schema(fn ($s) => ['email' => $s->string()->format('email')->required()]);

        $this->assertTrue($this->passes($schema, ['email' => 'taylor@laravel.com']));
    }

    public function test_date_format()
    {
        $schema = $this->schema(fn ($s) => ['on' => $s->string()->format('date')->required()]);

        $this->assertTrue($this->passes($schema, ['on' => '2026-11-06']));
        $this->assertFalse($this->passes($schema, ['on' => '2026-02-30']));
        $this->assertFalse($this->passes($schema, ['on' => '06/11/2026']));
        $this->assertFalse($this->passes($schema, ['on' => '2026-11-06T10:30:00Z']));
    }

    public function test_unknown_format_adds_no_rule()
    {
        $schema = $this->schema(fn ($s) => ['host' => $s->string()->format('hostname')->required()]);

        $this->assertTrue($this->passes($schema, ['host' => 'not a hostname']));
    }

    public function test_annotations_add_no_rules()
    {
        $schema = [
            'type' => 'object',
            'properties' => ['name' => ['type' => 'string', 'title' => 'Name', 'description' => 'The name.', 'default' => 'Taylor', 'examples' => ['Taylor']]],
        ];

        $this->assertTrue($this->passes($schema, []));
        $this->assertTrue($this->passes($schema, ['name' => 'Abigail']));
        $this->assertFalse($this->passes($schema, ['name' => 5]));
    }

    public function test_required_nullable_rejects_missing_and_empty_string()
    {
        $schema = $this->schema(fn ($s) => ['note' => $s->string()->nullable()->required()]);

        $this->assertFalse($this->passes($schema, []));
        $this->assertFalse($this->passes($schema, ['note' => '']));
        $this->assertTrue($this->passes($schema, ['note' => 'text']));
    }

    public function test_optional_nullable_rejects_empty_string()
    {
        $schema = $this->schema(fn ($s) => ['note' => $s->string()->nullable()]);

        $this->assertFalse($this->passes($schema, ['note' => '']));
        $this->assertTrue($this->passes($schema, []));
    }

    public function test_optional_value_rejects_null()
    {
        $schema = $this->schema(fn ($s) => ['note' => $s->string()]);

        $this->assertFalse($this->passes($schema, ['note' => null]));
    }

    public function test_nullable_any_of_accepts_null()
    {
        $schema = $this->schema(fn ($s) => ['plan' => $s->anyOf([$s->string(), $s->integer()])->nullable()->required()]);

        $this->assertTrue($this->passes($schema, ['plan' => null]));
        $this->assertTrue($this->passes($schema, ['plan' => 'Pro']));
        $this->assertFalse($this->passes($schema, ['plan' => ['a']]));
        $this->assertFalse($this->passes($schema, []));
    }

    public function test_any_of_branch_checks_nested_objects()
    {
        $schema = $this->schema(fn ($s) => ['target' => $s->anyOf([
            $s->object(['url' => $s->string()->format('url')->required()]),
            $s->object(['email' => $s->string()->format('email')->required()]),
        ])->required()]);

        $this->assertTrue($this->passes($schema, ['target' => ['email' => 'taylor@laravel.com']]));
        $this->assertFalse($this->passes($schema, ['target' => ['email' => 'nope']]));
    }

    public function test_nullable_union_accepts_null()
    {
        $schema = $this->schema(fn ($s) => ['id' => $s->union(['string', 'integer', 'null'])->required()]);

        $this->assertTrue($this->passes($schema, ['id' => null]));
        $this->assertTrue($this->passes($schema, ['id' => 7]));
        $this->assertFalse($this->passes($schema, ['id' => ['a']]));
    }

    public function test_union_applies_type_keywords_to_each_branch()
    {
        $schema = ['type' => 'object', 'properties' => ['id' => ['type' => ['string', 'integer'], 'enum' => ['a', 1]]], 'required' => ['id']];

        $this->assertTrue($this->passes($schema, ['id' => 'a']));
        $this->assertTrue($this->passes($schema, ['id' => 1]));
        $this->assertFalse($this->passes($schema, ['id' => 'b']));
    }

    public function test_nested_object_allows_unknown_keys_unless_additional_properties_is_false()
    {
        $open = $this->schema(fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->required()]);
        $closed = $this->schema(fn ($s) => ['address' => $s->object(['city' => $s->string()->required()])->withoutAdditionalProperties()->required()]);

        $data = ['address' => ['city' => 'Lisbon', 'admin' => true]];

        $this->assertTrue($this->passes($open, $data));
        $this->assertFalse($this->passes($closed, $data));
    }

    public function test_array_items_are_checked_against_nested_object_schemas()
    {
        $schema = $this->schema(fn ($s) => ['people' => $s->array()->items($s->object(['age' => $s->integer()->min(0)->required()]))->required()]);

        $this->assertTrue($this->passes($schema, ['people' => [['age' => 1], ['age' => 2]]]));
        $this->assertFalse($this->passes($schema, ['people' => [['age' => 1], ['age' => -1]]]));
    }

    /**
     * Build an object schema array from the given properties.
     *
     * @return array<string, mixed>
     */
    protected function schema(Closure $properties): array
    {
        return (new ObjectType($properties(new JsonSchemaTypeFactory)))->toArray();
    }

    /**
     * Build an object schema array with additional properties disabled on every nested object.
     *
     * @return array<string, mixed>
     */
    protected function strict(Closure $properties): array
    {
        return $this->withoutAdditionalProperties($this->schema($properties));
    }

    /**
     * Recursively set "additionalProperties" to false on every object schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function withoutAdditionalProperties(array $schema): array
    {
        if (in_array('object', (array) ($schema['type'] ?? []), true)) {
            $schema['additionalProperties'] = false;

            foreach ($schema['properties'] ?? [] as $key => $property) {
                $schema['properties'][$key] = $this->withoutAdditionalProperties($property);
            }
        }

        if (isset($schema['items'])) {
            $schema['items'] = $this->withoutAdditionalProperties($schema['items']);
        }

        foreach ($schema['anyOf'] ?? [] as $key => $branch) {
            $schema['anyOf'][$key] = $this->withoutAdditionalProperties($branch);
        }

        return $schema;
    }

    /**
     * Determine if the data passes the rules derived from the schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<array-key, mixed>  $data
     */
    protected function passes(array $schema, array $data): bool
    {
        return Validator::make($data, JsonSchema::rules($schema))->passes();
    }
}
