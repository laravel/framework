<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\Attributes\Attributes;
use Illuminate\Http\Resources\JsonApi\Attributes\Relationships;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

#[Attributes(attributes: ['title', 'content'])]
#[Relationships(relationships: ['author' => AuthorResource::class, 'comments'])]
class AttributeBasedPostResource extends JsonApiResource
{
}
