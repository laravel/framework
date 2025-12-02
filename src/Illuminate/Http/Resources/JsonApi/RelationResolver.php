<?php

namespace Illuminate\Http\Resources\JsonApi;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal
 */
class RelationResolver
{
    /**
     * @var \Closure(mixed):(\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null)
     */
    public Closure $relationResolver;

    /**
     * @var class-string<\Illuminate\Http\Resources\JsonApi\JsonApiResource>|null
     */
    public ?string $relationResourceClass = null;

    /**
     * Construct a new resource relationship resolver.
     *
     * @param  \Closure(mixed):(\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null)|class-string<\Illuminate\Http\Resources\JsonApi\JsonApiResource>|null  $resolver
     */
    public function __construct(
        public string $relationName,
        Closure|string|null $resolver = null
    ) {
        $this->relationResolver = match (true) {
            $resolver instanceof Closure => $resolver,
            default => fn ($resource) => $resource->getRelation($this->relationName),
        };

        if (is_string($resolver) && class_exists($resolver)) {
            $this->relationResourceClass = $resolver;
        }
    }

    /**
     * Resolve relation for a resource.
     */
    public function handle(mixed $resource): Collection|Model|null
    {
        return value($this->relationResolver, $resource);
    }

    /**
     * Get the resource class.
     *
     * @return class-string<\Illuminate\Http\Resources\JsonApi\JsonApiResource>|null
     */
    public function resourceClass(): ?string
    {
        return $this->relationResourceClass;
    }

    public function resourceType(Collection|Model|null $resources, JsonApiRequest $request): ?string
    {
        if (is_null($resourceClass = $this->resourceClass())) {
            return null;
        }

        $resource = $resources instanceof Collection ? $resources->first() : $resources;

        return (new $resourceClass($resource))->toType($request);
    }
}
