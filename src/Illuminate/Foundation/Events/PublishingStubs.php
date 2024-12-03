<?php

namespace Illuminate\Foundation\Events;

class PublishingStubs
{
    use Dispatchable;

    /**
     * The stubs being published.
     *
     * @var array
     */
    public $stubs = [];

    /**
     * Create a new event instance.
     *
     * @param  array  $stubs
     * @return void
     */
    public function __construct(array $stubs)
    {
        $this->stubs = $stubs;
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
        return tap($this, fn () => $this->stubs[$path] = $name);
    }
}
