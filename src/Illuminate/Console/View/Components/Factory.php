<?php

namespace Illuminate\Console\View\Components;

use InvalidArgumentException;

class Factory
{
    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * Creates a new factory instance.
     *
     * @param  \Illuminate\Console\OutputStyle  $output
     * @return void
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * Dynamically handle calls into the component instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __call($method, $parameters)
    {
        $component = '\Illuminate\Console\View\Components\\'.ucfirst($method);

        throw_unless(class_exists($component), new InvalidArgumentException(sprintf(
            'Console component [%s] not found.', $method
        )));

        return with(new $component($this->output))->render(...$parameters);
    }
}
