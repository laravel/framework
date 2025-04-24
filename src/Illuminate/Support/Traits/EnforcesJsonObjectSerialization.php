<?php

namespace Illuminate\Support\Traits;

trait EnforcesJsonObjectSerialization
{
    /**
     * The attributes that should always be serialized as JSON objects.
     *
     * @var list<string>
     */
    protected array $serializeAsObjects = [];

    /**
     * Determines if empty arrays should be cast to objects when serialized to JSON.
     */
    protected bool $serializeEmptyAsObject = false;

    /**
     * Convert empty arrays to objects during JSON serialization based on configuration.
     *
     * This method handles two use cases:
     * 1. Converting an entire empty array/collection to an object (useful for collections)
     * 2. Converting specific attributes to objects regardless of their content (useful for models)
     */
    protected function enforceJsonObjectSerialization(array $data): array|object
    {
        // Fast path for collection case - entire dataset as object when empty
        if (empty($data) && $this->serializeEmptyAsObject) {
            return (object) [];
        }

        // Fast path for attribute case - if no attributes configured or data is empty
        if (empty($data) || empty($this->serializeAsObjects)) {
            return $data;
        }

        // Handle specific attributes
        foreach ($this->serializeAsObjects as $attribute) {
            // Only transform arrays, preserve null values
            if (isset($data[$attribute]) && is_array($data[$attribute]) && empty($data[$attribute])) {
                $data[$attribute] = (object) [];
            }
        }

        return $data;
    }

    /**
     * Configure collection to be serialized as an object when empty.
     */
    public function serializeEmptyAsObject(bool $value = true): static
    {
        $this->serializeEmptyAsObject = $value;

        return $this;
    }

    /**
     * Configure specific attributes to be serialized as objects.
     *
     * @param  list<string>|string  $attributes
     */
    public function serializeAttributesAsObjects(array|string $attributes): static
    {
        $this->serializeAsObjects = is_array($attributes)
            ? $attributes
            : func_get_args();

        return $this;
    }
}
