<?php

namespace Illuminate\Routing;

class ControllerMiddlewareOptions
{
    /**
     * The middleware options.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new middleware option instance.
     *
     * @param  array  $options
     * @return void
     */
    public function __construct(array &$options)
    {
        $this->options = &$options;
    }

    /**
     * Set the controller methods the middleware should apply to.
     *
     * @param  array|string|mixed  $methods
     * @return $this
     */
    public function only($methods)
    {
        return tap($this, fn () => $this->options['only'] = is_array($methods) ? $methods : func_get_args());
    }

    /**
     * Set the controller methods the middleware should exclude.
     *
     * @param  array|string|mixed  $methods
     * @return $this
     */
    public function except($methods)
    {
        return tap($this, fn () => $this->options['except'] = is_array($methods) ? $methods : func_get_args());
    }
}
