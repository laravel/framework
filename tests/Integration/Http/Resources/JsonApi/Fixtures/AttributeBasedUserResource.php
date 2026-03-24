<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\Attributes\Attributes;
use Illuminate\Http\Resources\JsonApi\Attributes\Relationships;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

#[Attributes(attributes: ['name', 'email'])]
#[Relationships(relationships: ['comments', 'profile', 'posts'])]
class AttributeBasedUserResource extends JsonApiResource
{
}
