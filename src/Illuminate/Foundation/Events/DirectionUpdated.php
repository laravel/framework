<?php

namespace Illuminate\Foundation\Events;

class DirectionUpdated
{
    /**
     * The new direction.
     *
     * @var string
     */
    public $direction;

    /**
     * Create a new event instance.
     *
     * @param  string  $direction
     * @return void
     */
    public function __construct($direction)
    {
        $this->direction = $direction;
    }
}
