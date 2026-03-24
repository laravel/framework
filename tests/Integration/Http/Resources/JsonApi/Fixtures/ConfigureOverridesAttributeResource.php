<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\Attributes\Attributes;
use Illuminate\Http\Resources\JsonApi\Attributes\JsonApiInformation;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

#[JsonApiInformation(version: '1.0', ext: ['atomic'], profile: ['https://example.com/profiles/blog'])]
#[Attributes(attributes: ['name', 'email'])]
class ConfigureOverridesAttributeResource extends JsonApiResource
{
}
