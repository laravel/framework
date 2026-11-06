<?php

namespace Illuminate\JsonSchema;

use Closure;
use Illuminate\JsonSchema\Rules\RejectEmptyString;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidationRules
{
    /**
     * Get the validation rules for the properties of the given object schema.
     *
     * @param  \Illuminate\JsonSchema\Types\Type|array<string, mixed>  $schema
     * @return array<string, array<int, mixed>>
     */
    public static function for(Type|array $schema): array
    {
        return static::properties($schema instanceof Type ? $schema->toArray() : $schema);
    }

    /**
     * Get the rules for each property of an object schema, keyed under the given prefix.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, array<int, mixed>>
     */
    protected static function properties(array $schema, ?string $prefix = null): array
    {
        $required = array_map('strval', $schema['required'] ?? []);

        $rules = [];

        foreach ($schema['properties'] ?? [] as $name => $property) {
            $name = (string) $name;
            $key = str_replace('.', '\\.', $name);

            $rules += static::rules(
                $property,
                $prefix === null ? $key : "{$prefix}.{$key}",
                in_array($name, $required, true),
                $prefix !== null,
            );
        }

        return $rules;
    }

    /**
     * Get the rules for one value and everything nested inside it.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, array<int, mixed>>
     */
    protected static function rules(array $schema, string $attribute, bool $required, bool $nested = false): array
    {
        $isNull = fn (array $branch) => ($branch['type'] ?? null) === 'null';

        $nullable = in_array('null', (array) ($schema['type'] ?? []), true)
            || array_filter($schema['anyOf'] ?? [], $isNull) !== [];

        // A nested required key is enforced by its parent's "required_array_keys", so an absent parent passes...
        $presence = match (true) {
            $required && ! $nested => $nullable ? ['present', 'nullable', new RejectEmptyString] : ['required'],
            $required && ! $nullable => ['sometimes', 'filled'],
            $nullable => ['sometimes', 'nullable', new RejectEmptyString],
            default => ['sometimes', new RejectEmptyString],
        };

        if (isset($schema['anyOf'])) {
            $branches = array_values(array_filter($schema['anyOf'], fn (array $branch) => ! $isNull($branch)));

            return [$attribute => [...$presence, static::anyOf($branches), ...static::enumRules($schema)]];
        }

        $types = array_values(array_diff((array) ($schema['type'] ?? []), ['null']));

        if (count($types) > 1) {
            $branches = array_map(fn (string $type) => [...$schema, 'type' => $type], $types);

            return [$attribute => [...$presence, static::anyOf($branches)]];
        }

        $type = $types[0] ?? null;

        $nested = match ($type) {
            'array' => isset($schema['items']) ? static::rules($schema['items'], "{$attribute}.*", false) : [],
            'object' => static::properties($schema, $attribute),
            default => [],
        };

        if ($type === 'array' && ($schema['uniqueItems'] ?? false)) {
            $nested["{$attribute}.*"] = [...$nested["{$attribute}.*"] ?? [], 'distinct'];
        }

        return [$attribute => [...$presence, ...static::typeRules($type, $schema)]] + $nested;
    }

    /**
     * Get the rules a value of the given type must pass.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, mixed>
     */
    protected static function typeRules(?string $type, array $schema): array
    {
        $rules = match ($type) {
            'string' => ['string', ...static::bounds($schema, 'minLength', 'maxLength'), ...static::stringRules($schema)],
            'integer' => ['integer', ...static::numberRules($schema)],
            'number' => ['numeric', ...static::numberRules($schema)],
            'boolean' => ['boolean'],
            'array' => [
                'list',
                ...static::bounds($schema, 'minItems', 'maxItems'),
                ...($schema['uniqueItems'] ?? false) ? [static::uniqueArrayItems()] : [],
            ],
            'object' => static::objectRules($schema),
            default => [],
        };

        return [...$rules, ...static::enumRules($schema)];
    }

    /**
     * Get the rule that restricts a value to the schema's enumerated values.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, mixed>
     */
    protected static function enumRules(array $schema): array
    {
        if (! isset($schema['enum'])) {
            return [];
        }

        $enum = $schema['enum'];

        if (array_filter($enum, is_array(...)) === []) {
            return [Rule::in($enum)];
        }

        return [function (string $attribute, mixed $value, Closure $fail) use ($enum) {
            if (! in_array($value, $enum)) {
                $fail('validation.in')->translate();
            }
        }];
    }

    /**
     * Build a rule that refuses an array holding two equal array items, which "distinct" does not compare.
     */
    protected static function uniqueArrayItems(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $items = array_values(array_filter((array) $value, is_array(...)));

            foreach ($items as $index => $item) {
                if (in_array($item, array_slice($items, $index + 1))) {
                    $fail('validation.distinct')->translate();

                    return;
                }
            }
        };
    }

    /**
     * Get the rules an object value must pass.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    protected static function objectRules(array $schema): array
    {
        $keys = array_map('strval', array_keys($schema['properties'] ?? []));

        $rules = match (true) {
            ($schema['additionalProperties'] ?? null) !== false => ['array'],
            $keys === [] => ['array', 'max:0'],
            default => ['array:'.static::csv($keys)],
        };

        $required = array_map('strval', $schema['required'] ?? []);

        return $required === [] ? $rules : [...$rules, 'required_array_keys:'.static::csv($required)];
    }

    /**
     * Join the given values into a quoted rule parameter list.
     *
     * @param  array<int, string>  $values
     */
    protected static function csv(array $values): string
    {
        return implode(',', array_map(fn (string $value) => '"'.str_replace('"', '""', $value).'"', $values));
    }

    /**
     * Get the "min" and "max" rules for the given schema keywords.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    protected static function bounds(array $schema, string $min, string $max): array
    {
        return array_values(array_filter([
            isset($schema[$min]) ? "min:{$schema[$min]}" : null,
            isset($schema[$max]) ? "max:{$schema[$max]}" : null,
        ]));
    }

    /**
     * Get the bound and "multiple_of" rules for a numeric schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    protected static function numberRules(array $schema): array
    {
        return [
            ...static::bounds($schema, 'minimum', 'maximum'),
            ...isset($schema['multipleOf']) ? ["multiple_of:{$schema['multipleOf']}"] : [],
        ];
    }

    /**
     * Get the pattern and format rules for a string schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    protected static function stringRules(array $schema): array
    {
        return [
            ...isset($schema['pattern']) ? ['regex:/'.static::escapeDelimiter($schema['pattern']).'/u'] : [],
            ...match ($schema['format'] ?? null) {
                'email' => ['email'],
                'uri', 'url' => ['url'],
                'uuid' => ['uuid'],
                'date' => ['date_format:Y-m-d'],
                'date-time' => ['regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/i', 'date'],
                default => [],
            },
        ];
    }

    /**
     * Escape each "/" in the pattern that is not already escaped by an odd number of backslashes.
     */
    protected static function escapeDelimiter(string $pattern): string
    {
        return preg_replace('~(?<!\\\\)((?:\\\\\\\\)*)/~', '$1\\/', $pattern);
    }

    /**
     * Build a rule that passes when the value matches any of the given schemas.
     *
     * @param  array<int, array<string, mixed>>  $branches
     */
    protected static function anyOf(array $branches): Closure
    {
        $branches = array_map(fn (array $branch) => static::rules($branch, 'value', true), $branches);

        return function (string $attribute, mixed $value, Closure $fail) use ($branches) {
            foreach ($branches as $rules) {
                if (Validator::make(['value' => $value], $rules)->passes()) {
                    return;
                }
            }

            $fail('validation.any_of')->translate();
        };
    }
}
