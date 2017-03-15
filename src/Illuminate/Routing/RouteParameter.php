<?php

namespace Illuminate\Routing;

class RouteParameter
{
    /**
     * The string representation of the parameter.
     *
     * @var string
     */
    protected $parameter;

    /**
     * The (optional) value of the parameter.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new Route Parameter instance.
     * RouteParameter constructor.
     *
     * @param  string $parameter
     * @param  mixed  $value
     * @return void
     */
    public function __construct($parameter, $value = null)
    {
        $this->parameter = $parameter;
        $this->value = $value;
    }

    /**
     * Get the name from the parameter definition
     * by finding what's before the semicolon.
     *
     * @return string|null
     */
    public function name()
    {
        return $this->parts()[0];
    }

    /**
     * Get the key from the parameter definition
     * by finding what's after the semicolon.
     *
     * @return string|null
     */
    public function key()
    {
        return isset($this->parts()[1])
            ? $this->parts()[1]
            : null;
    }

    /**
     * Get the value of the parameter.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Get the original parameter definition.
     *
     * @return string
     */
    public function parameter(): string
    {
        return $this->parameter;
    }

    /**
     * Explode by a semicolon to split
     * the name and the key.
     *
     * @return array
     */
    protected function parts()
    {
        return explode(':', $this->parameter, 2);
    }
}
