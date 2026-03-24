<?php

namespace Illuminate\Http\Resources\JsonApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class JsonApiInformation
{
    public function __construct(
        public ?string $version = null,
        public array $ext = [],
        public array $profile = [],
        public array $meta = [],
    ) {
    }
}
