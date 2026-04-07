<?php

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Broadcasts
{
    /**
     * Create a new attribute instance.
     */
    public function __construct(
        public ?string $connection = null,
        public ?string $queue = null,
        public ?bool $afterCommit = null,
    ) {
    }
}
