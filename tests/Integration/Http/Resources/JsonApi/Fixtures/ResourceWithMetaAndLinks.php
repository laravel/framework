<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\Attributes\Attributes;
use Illuminate\Http\Resources\JsonApi\Attributes\JsonApiLinks;
use Illuminate\Http\Resources\JsonApi\Attributes\JsonApiMeta;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

#[JsonApiMeta(meta: ['copyright' => '2024 Laravel'])]
#[JsonApiLinks(links: ['self' => 'https://example.com/users/1'])]
#[Attributes(attributes: ['name', 'email'])]
class ResourceWithMetaAndLinks extends JsonApiResource
{
}
