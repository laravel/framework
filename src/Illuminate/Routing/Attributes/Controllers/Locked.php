<?php

namespace Illuminate\Routing\Attributes\Controllers;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Locked
{
    /**
     * Create a new attribute instance.
     *
     * @param  string|null  $key
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return void
     */
    public function __construct(
        public ?string $key = null,
        public int $seconds = 10,
        public ?string $owner = null
    ) {
    }
}

