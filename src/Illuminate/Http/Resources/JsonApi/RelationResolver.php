<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RelationResolver
{
    public $relationResolver;

    public function __construct(
        public string $relationName,
        Closure|string|null $resolver = null
    ) {
        $this->relationResolver = $resolver ?? fn ($resource) => $resource->getRelation($this->relationName);
    }
    public function handle(mixed $resource): Collection|Model
    {
        return value($this->relationResolver, $resource);
    }
}
