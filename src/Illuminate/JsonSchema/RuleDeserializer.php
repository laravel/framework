<?php

namespace Illuminate\JsonSchema;

class RuleDeserializer
{
    /**
     * The validation rules that resolve a property to a type.
     *
     * @var array<string, string>
     */
    protected const TYPES = [
        'string' => 'string',
        'integer' => 'integer',
        'int' => 'integer',
        'numeric' => 'number',
        'decimal' => 'number',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'array' => 'array',
        'list' => 'array',
    ];

    /**
     * The validation rules that map to a string format.
     *
     * @var array<string, string>
     */
    protected const FORMATS = [
        'email' => 'email',
        'url' => 'uri',
        'uuid' => 'uuid',
        'ipv4' => 'ipv4',
        'ipv6' => 'ipv6',
        'date' => 'date',
    ];

    /**
     * Build a schema from the given set of Laravel validation rules.
     *
     * @param  array<string, mixed>  $rules
     */
    public static function deserialize(array $rules): Types\ObjectType
    {
        return (new static)->build(static::tree($rules));
    }

    /**
     * Arrange the flat, "dot" notated rules into a nested tree.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, array{rules: array<int, string>, children: array<string, mixed>}>
     */
    protected static function tree(array $rules): array
    {
        $tree = [];

        foreach ($rules as $key => $definition) {
            $node = &$tree;

            foreach (explode('.', (string) $key) as $segment) {
                $node[$segment] ??= ['rules' => [], 'children' => []];

                $leaf = &$node[$segment];
                $node = &$leaf['children'];
            }

            $leaf['rules'] = static::normalize($definition);

            unset($node, $leaf);
        }

        return $tree;
    }

    /**
     * Reduce a rule definition to the string rules it contains.
     *
     * @return array<int, string>
     */
    protected static function normalize(mixed $definition): array
    {
        $rules = match (true) {
            is_string($definition) => explode('|', $definition),
            is_array($definition) => $definition,
            default => [],
        };

        // Rule objects, closures and invokable rules describe constraints that
        // have no JSON Schema equivalent, so only the string rules are kept...
        return array_values(array_filter($rules, 'is_string'));
    }

    /**
     * Build the schema for a tree of properties.
     *
     * @param  array<string, array{rules: array<int, string>, children: array<string, mixed>}>  $tree
     */
    protected function build(array $tree): Types\ObjectType
    {
        $properties = [];

        foreach ($tree as $name => $node) {
            $properties[$name] = $this->property($node);
        }

        return new Types\ObjectType($properties);
    }

    /**
     * Build the schema for a single property.
     *
     * @param  array{rules: array<int, string>, children: array<string, mixed>}  $node
     */
    protected function property(array $node): Types\Type
    {
        [$names, $parameters] = $this->parse($node['rules']);

        $type = $this->resolve($names, $parameters, $node['children']);

        $this->applyConstraints($type, $names, $parameters);

        if (in_array('required', $names, true)) {
            $type->required();
        }

        if (in_array('nullable', $names, true)) {
            $type->nullable();
        }

        return $type;
    }

    /**
     * Split the rules into their names and parameters.
     *
     * @param  array<int, string>  $rules
     * @return array{0: array<int, string>, 1: array<string, string>}
     */
    protected function parse(array $rules): array
    {
        $names = [];
        $parameters = [];

        foreach ($rules as $rule) {
            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

            $name = strtolower(trim($name));

            $names[] = $name;

            if ($parameter !== null) {
                $parameters[$name] = $parameter;
            }
        }

        return [$names, $parameters];
    }

    /**
     * Resolve the type a property describes.
     *
     * @param  array<int, string>  $names
     * @param  array<string, string>  $parameters
     * @param  array<string, mixed>  $children
     */
    protected function resolve(array $names, array $parameters, array $children): Types\Type
    {
        if (isset($children['*'])) {
            return (new Types\ArrayType)->items($this->property($children['*']));
        }

        if ($children !== []) {
            return $this->build($children);
        }

        foreach ($names as $name) {
            if (isset(static::TYPES[$name])) {
                return $this->make(static::TYPES[$name]);
            }
        }

        // A field carrying no type rule is treated as a string, matching how the
        // validator sizes an untyped value against "min", "max" and "size"...
        return $this->make('string');
    }

    /**
     * Create a new type instance for the given JSON Schema type name.
     */
    protected function make(string $type): Types\Type
    {
        return match ($type) {
            'integer' => new Types\IntegerType,
            'number' => new Types\NumberType,
            'boolean' => new Types\BooleanType,
            'array' => new Types\ArrayType,
            default => new Types\StringType,
        };
    }

    /**
     * Apply the constraint rules supported for the resolved type.
     *
     * @param  array<int, string>  $names
     * @param  array<string, string>  $parameters
     */
    protected function applyConstraints(Types\Type $type, array $names, array $parameters): void
    {
        foreach ($names as $name) {
            match ($name) {
                'min' => $this->bound($type, 'min', $parameters['min'] ?? null),
                'max' => $this->bound($type, 'max', $parameters['max'] ?? null),
                'size' => $this->size($type, $parameters['size'] ?? null),
                'between' => $this->between($type, $parameters['between'] ?? null),
                'in' => $this->enum($type, $parameters['in'] ?? null),
                'regex' => $this->pattern($type, $parameters['regex'] ?? null),
                'multiple_of' => $this->multipleOf($type, $parameters['multiple_of'] ?? null),
                default => $this->format($type, $name),
            };
        }
    }

    /**
     * Apply a lower or upper bound in the unit the resolved type is measured in.
     */
    protected function bound(Types\Type $type, string $bound, ?string $value): void
    {
        if ($value === null || ! is_numeric($value)) {
            return;
        }

        match (true) {
            $type instanceof Types\StringType => $bound === 'min'
                ? $type->min((int) $value)
                : $type->max((int) $value),
            $type instanceof Types\IntegerType => $bound === 'min'
                ? $type->min((int) $value)
                : $type->max((int) $value),
            $type instanceof Types\NumberType => $bound === 'min'
                ? $type->min($value + 0)
                : $type->max($value + 0),
            $type instanceof Types\ArrayType => $bound === 'min'
                ? $type->min((int) $value)
                : $type->max((int) $value),
            default => null,
        };
    }

    /**
     * Apply an exact size as a matching pair of bounds.
     */
    protected function size(Types\Type $type, ?string $value): void
    {
        $this->bound($type, 'min', $value);
        $this->bound($type, 'max', $value);
    }

    /**
     * Apply the pair of bounds a "between" rule describes.
     */
    protected function between(Types\Type $type, ?string $value): void
    {
        [$min, $max] = array_pad(explode(',', (string) $value, 2), 2, null);

        $this->bound($type, 'min', $min);
        $this->bound($type, 'max', $max);
    }

    /**
     * Apply the step a numeric value must be a multiple of.
     */
    protected function multipleOf(Types\Type $type, ?string $value): void
    {
        if ($value === null || ! is_numeric($value)) {
            return;
        }

        match (true) {
            $type instanceof Types\IntegerType => $type->multipleOf((int) $value),
            $type instanceof Types\NumberType => $type->multipleOf($value + 0),
            default => null,
        };
    }

    /**
     * Apply the set of values an "in" rule allows.
     */
    protected function enum(Types\Type $type, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $type->enum(array_map(
            static fn (string $value) => trim($value, '"'),
            explode(',', $value),
        ));
    }

    /**
     * Apply a regular expression, without its delimiters, as a pattern.
     */
    protected function pattern(Types\Type $type, ?string $value): void
    {
        if (! $type instanceof Types\StringType || $value === null || strlen($value) < 2) {
            return;
        }

        $delimiter = $value[0];

        if (($end = strrpos($value, $delimiter)) > 0) {
            $value = substr($value, 1, $end - 1);
        }

        $type->pattern($value);
    }

    /**
     * Apply the string format a rule describes, if it describes one.
     */
    protected function format(Types\Type $type, string $name): void
    {
        if ($type instanceof Types\StringType && isset(static::FORMATS[$name])) {
            $type->format(static::FORMATS[$name]);
        }
    }
}
