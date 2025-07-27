<?php

namespace Illuminate\Http\Resources\Json;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class MinimizedResource extends JsonResource
{
    /**
     * MinimizedResource constructor.
     *
     * @param mixed $resource     The resource data being transformed.
     * @param array $onlyFields   Optional list of specific fields to include in the response.
     */
    public function __construct($resource, protected array $onlyFields = [])
    {
        // Call the parent JsonResource constructor
        parent::__construct($resource);
    }

    /**
     * Determine whether the given field key should be included in the response.
     *
     * @param string $key   The name of the field being checked.
     * @return bool         True if no filtering is applied or the key is in the allowed list.
     */
    protected function show(string $key): bool
    {
        return empty($this->onlyFields) || in_array($key, $this->onlyFields);
    }

    /**
     * Create a collection of resources with only selected fields.
     */
    public static function collectionWithOnly($resources, array $only = [])
    {
        return collect($resources)->map(fn($item) => new static($item, $only));
    }
}
