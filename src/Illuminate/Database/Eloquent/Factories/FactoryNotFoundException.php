<?php

namespace Illuminate\Database\Eloquent\Factories;

use RuntimeException;

class FactoryNotFoundException extends RuntimeException
{
    /**
     * The factory that was not found.
     *
     * @var string
     */
    public $factory;

    /**
     * Create a new exception instance.
     *
     * @param  string  $factory
     * @return static
     */
    public function __construct($factory)
    {
        parent::__construct("Call to undefined factory [{$factory}]");

        $this->factory = $factory;
    }
}
