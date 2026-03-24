<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Http\Resources\JsonApi\Attributes\Attributes;
use Illuminate\Http\Resources\JsonApi\Attributes\Wraps;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

#[Wraps(wrapper: 'result')]
#[Attributes(attributes: ['name', 'email'])]
class ResourceWithCustomWrap extends JsonApiResource
{
}
