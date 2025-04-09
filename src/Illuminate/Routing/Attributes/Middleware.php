<?php

namespace Illuminate\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(
        public string $middlewareClass,
        public array $parameters = []
    ) {
    }

    public function toMiddlewareString(): string
    {
        if (! empty($this->parameters)) {
            return $this->middlewareClass.':'.implode(',', $this->parameters);
        }

        return $this->middlewareClass;
    }
}
