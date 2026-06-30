<?php

namespace Illuminate\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\InteractsWithData;

class CommandInput
{
    use Dumpable, InteractsWithData;

    /**
     * The command arguments.
     *
     * @var array
     */
    protected $arguments;

    /**
     * The command options.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new command input container.
     *
     * @param  array  $arguments
     * @param  array  $options
     */
    public function __construct(array $arguments = [], array $options = [])
    {
        $this->arguments = $arguments;
        $this->options = $options;
    }

    /**
     * Get all of the input for the command.
     *
     * Options take precedence over arguments when keys collide.
     *
     * @param  mixed  $keys
     * @return array
     */
    public function all($keys = null)
    {
        $input = array_merge($this->options, $this->arguments);

        if (! $keys) {
            return $input;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    /**
     * Retrieve data from the instance.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function data($key = null, $default = null)
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * Get all of the arguments passed to the command.
     *
     * @return array
     */
    public function arguments()
    {
        return $this->arguments;
    }

    /**
     * Get all of the options passed to the command.
     *
     * @return array
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Dynamically access input data.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data($name);
    }

    /**
     * Determine if an input item is set.
     *
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->exists($name);
    }
}
