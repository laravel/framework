<?php

namespace Illuminate\JsonSchema\Types;

use InvalidArgumentException;

class UnionType extends Type
{
    /**
     * The JSON Schema primitive type names a union may be composed of.
     *
     * @var array<int, string>
     */
    public const SUPPORTED = ['string', 'integer', 'number', 'boolean', 'object', 'array'];

    /**
     * The union's member type names.
     *
     * Members carry their names only (mirroring a bare "type": [...] array);
     * they do not hold their own keyword constraints.
     *
     * @var array<int, string>
     */
    protected array $types;

    /**
     * Create a new union type instance.
     *
     * @param  array<int, string>  $types
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $types)
    {
        $names = array_map('strval', $types);

        // A "null" member expresses nullability, it is never a union member...
        if (in_array('null', $names, true)) {
            $this->nullable();

            $names = array_filter($names, static fn (string $name) => $name !== 'null');
        }

        foreach ($names as $name) {
            if (! in_array($name, self::SUPPORTED, true)) {
                throw new InvalidArgumentException("Unsupported JSON Schema type [{$name}] in a multi-type union.");
            }
        }

        // Keep the declared order while removing duplicate member names...
        $this->types = array_values(array_unique($names));
    }

    /**
     * Get the union's member type names.
     *
     * @return array<int, string>
     */
    public function types(): array
    {
        return $this->types;
    }
}
