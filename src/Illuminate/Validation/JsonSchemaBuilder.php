<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\CompilableRules;
use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;

class JsonSchemaBuilder
{
    /**
     * The validation rules that map directly to a JSON Schema string format.
     *
     * @var array<string, string>
     */
    protected const FORMATS = [
        'Email' => 'email',
        'Url' => 'uri',
        'Uuid' => 'uuid',
        'Ipv4' => 'ipv4',
        'Ipv6' => 'ipv6',
        'Date' => 'date-time',
    ];

    /**
     * Build an object schema from the given validation rules.
     *
     * @param  array<string, mixed>  $rules
     */
    public static function fromRules(array $rules): ObjectType
    {
        $tree = [];

        $builder = new static;

        // Dotted attributes nest into child nodes and "*" segments become the
        // node describing the items of an array, mirroring the rule syntax...
        foreach ($rules as $attribute => $attributeRules) {
            Arr::set(
                $tree,
                str_replace('.', '.children.', $attribute).'.rules',
                $builder->parse($attribute, $attributeRules)
            );
        }

        return $builder->object($tree);
    }

    /**
     * Parse the rules of a single attribute into [name, parameters] pairs.
     *
     * @return array<int, array{0: string, 1: array}>
     */
    protected function parse(string $attribute, mixed $rules): array
    {
        if ($rules instanceof CompilableRules) {
            return [];
        }

        if (is_array($rules)) {
            $rules = array_filter($rules, fn ($rule) => ! $rule instanceof CompilableRules);
        }

        $parsed = [];

        foreach ((new ValidationRuleParser([]))->mergeRules([], $attribute, $rules)[$attribute] as $rule) {
            $rule = match (true) {
                $rule instanceof Enum => (string) $rule,
                $rule instanceof Email => 'email',
                default => $rule,
            };

            if ($rule instanceof ParameterizedRule || (is_string($rule) && $rule !== '')) {
                $parsed[] = ValidationRuleParser::parse($rule);
            }
        }

        return $parsed;
    }

    /**
     * Build an object schema from the given attribute nodes.
     *
     * @param  array<string, array{rules?: array, children?: array}>  $nodes
     */
    protected function object(array $nodes): ObjectType
    {
        return new ObjectType(array_map($this->property(...), $nodes));
    }

    /**
     * Build the schema for a single attribute node.
     *
     * @param  array{rules?: array, children?: array}  $node
     */
    protected function property(array $node): Type
    {
        $rules = $node['rules'] ?? [];

        $type = $this->type($rules, $node['children'] ?? []);

        foreach ($rules as [$name, $parameters]) {
            match ($name) {
                'Required' => $type->required(),
                'Nullable' => $type->nullable(),
                'In' => $type->enum($this->values($type, $parameters)),
                'Min' => $this->bound($type, min: $parameters[0] ?? null),
                'Max' => $this->bound($type, max: $parameters[0] ?? null),
                'Size' => $this->bound($type, $parameters[0] ?? null, $parameters[0] ?? null),
                'Between' => $this->bound($type, $parameters[0] ?? null, $parameters[1] ?? null),
                'MultipleOf' => $this->multipleOf($type, $parameters[0] ?? null),
                'Regex' => $type instanceof StringType ? $type->pattern($this->pattern($parameters[0] ?? '')) : null,
                'DateFormat' => $type instanceof StringType && in_array('Y-m-d', $parameters, true) ? $type->format('date') : null,
                default => $type instanceof StringType && isset(static::FORMATS[$name]) ? $type->format(static::FORMATS[$name]) : null,
            };
        }

        return $type;
    }

    /**
     * Determine the schema type of a node from its rules and children.
     *
     * @param  array<int, array{0: string, 1: array}>  $rules
     * @param  array<string, array{rules?: array, children?: array}>  $children
     */
    protected function type(array $rules, array $children): Type
    {
        if (isset($children['*'])) {
            $type = (new ArrayType)->items($this->property($children['*']));

            if (in_array('Distinct', array_column($children['*']['rules'] ?? [], 0), true)) {
                $type->unique();
            }

            return $type;
        }

        if ($children !== []) {
            return $this->object($children);
        }

        $names = array_column($rules, 0);

        $keys = array_column($rules, 1, 0)['Array'] ?? [];

        // Request input is a string unless a rule says otherwise, which is also
        // how the validator sizes an untyped value for "min", "max" and "size"...
        return match (true) {
            in_array('Integer', $names, true) => new IntegerType,
            in_array('Numeric', $names, true), in_array('Decimal', $names, true) => new NumberType,
            in_array('Boolean', $names, true) => new BooleanType,
            in_array('String', $names, true) => new StringType,
            in_array('List', $names, true) => new ArrayType,
            in_array('Array', $names, true) => $keys === []
                ? new ArrayType
                : new ObjectType(array_fill_keys($keys, new StringType)),
            default => new StringType,
        };
    }

    /**
     * Apply the given bounds in the unit the type is measured in.
     */
    protected function bound(Type $type, mixed $min = null, mixed $max = null): void
    {
        foreach (['min' => $min, 'max' => $max] as $method => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            if ($type instanceof StringType || $type instanceof ArrayType) {
                $type->{$method}((int) $value);
            } elseif ($type instanceof IntegerType) {
                $type->{$method}((int) ($method === 'min' ? ceil($value) : floor($value)));
            } elseif ($type instanceof NumberType) {
                $type->{$method}($value + 0);
            }
        }
    }

    /**
     * Apply the "multiple_of" rule to a numeric type.
     */
    protected function multipleOf(Type $type, mixed $value): void
    {
        if (! is_numeric($value)) {
            return;
        }

        if ($type instanceof IntegerType) {
            $type->multipleOf((int) $value);
        } elseif ($type instanceof NumberType) {
            $type->multipleOf($value + 0);
        }
    }

    /**
     * Cast the "in" rule values to the type's native representation.
     *
     * @param  array<int, string>  $values
     * @return array<int, mixed>
     */
    protected function values(Type $type, array $values): array
    {
        return array_map(fn ($value) => match (true) {
            $type instanceof IntegerType && is_numeric($value) => (int) $value,
            $type instanceof NumberType && is_numeric($value) => $value + 0,
            default => $value,
        }, $values);
    }

    /**
     * Strip the PCRE delimiters and modifiers from a "regex" rule pattern.
     */
    protected function pattern(string $pattern): string
    {
        $delimiter = $pattern[0] ?? '';

        if ($delimiter === '' || ctype_alnum($delimiter) || ctype_space($delimiter) || $delimiter === '\\') {
            return $pattern;
        }

        $closing = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'][$delimiter] ?? $delimiter;

        return ($end = strrpos($pattern, $closing)) > 0 ? substr($pattern, 1, $end - 1) : $pattern;
    }
}
