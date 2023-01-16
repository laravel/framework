<?php

namespace Illuminate\Foundation\Events;

class CollectStubsForPublishing
{
    use Dispatchable;

    /**
     * The stubs being published.
     *
     * @var array
     */
    private $stubs = [];

    /**
     * Create a new event instance.
     *
     * @param  array  $stubs
     */
    public function __construct($stubs)
    {
        $this->stubs = $stubs;
    }

    /**
     * Add a new stub for publishing.
     *
     * @param  string  $filpath
     * @param  string  $name
     *
     * @return $this
     */
    public function addStub($filpath, $name)
    {
        $this->stubs[$filpath] = $name;

        return $this;
    }

    /**
     * Get all the stubs for publishing.
     *
     * @return array
     */
    public function getStubs()
    {
        return $this->stubs;
    }
}