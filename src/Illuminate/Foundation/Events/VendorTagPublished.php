<?php

namespace Illuminate\Foundation\Events;

class VendorTagPublished
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $tag,
        public array $paths,
    ) {
    }
}
