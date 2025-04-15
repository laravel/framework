<?php

namespace Illuminate\Foundation\Events;

class VendorTagPublished
{
    /**
     * Create a new event instance.
     *
     * @param  string  $tag  The vendor tag that was published.
     * @param  array  $paths  The publishable paths registered by the tag.
     */
    public function __construct(
        public string $tag,
        public array $paths
    ) {
    }
}
