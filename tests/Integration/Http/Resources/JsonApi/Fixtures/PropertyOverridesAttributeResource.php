<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\Attributes\Attributes;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

#[Attributes(attributes: ['name'])]
class PropertyOverridesAttributeResource extends JsonApiResource
{
    protected array $attributes = [
        'name',
        'email',
    ];
}
