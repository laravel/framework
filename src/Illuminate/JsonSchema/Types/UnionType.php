<?php

namespace Illuminate\JsonSchema\Types;

class UnionType extends Type
{
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
     */
    public function __construct(array $types)
    {
        // Keep the declared order while removing duplicate member names...
        $this->types = array_values(array_unique(array_map('strval', $types)));
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
