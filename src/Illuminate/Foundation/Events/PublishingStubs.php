<?php

namespace Illuminate\Foundation\Events;

class PublishingStubs
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param  array  $stubs  The stubs being published.
     */
    public function __construct(
        public array $stubs = []
    ) {
    }

    /**
     * Add a new stub to be published.
     *
     * @param  string  $path
     * @param  string  $name
     * @return $this
     */
    public function add(string $path, string $name)
    {
        $this->stubs[$path] = $name;

        return $this;
    }
}
