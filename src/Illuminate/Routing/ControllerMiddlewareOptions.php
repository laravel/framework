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
     * @param  array  $methods
     * @return $this
     */
    public function only(...$methods)
    {
        $this->options['only'] = $methods;

        return $this;
    }

    /**
     * Set the controller methods the middleware should exclude.
     *
     * @param  array  $methods
     * @return $this
     */
    public function except(...$methods)
    {
        $this->options['except'] = $methods;

        return $this;
    }
}
