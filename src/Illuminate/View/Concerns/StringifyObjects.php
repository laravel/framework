<?php

namespace Illuminate\View\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

trait StringifyObjects
{
    /**
     * Custom rendering callbacks for stringable objects.
     *
     * @var array
     */
    protected $echoHandlers = [];

    /**
     * Add a handler to be executed before echoing a given class.
     *
     * @param  string|callable  $class
     * @param  callable|null  $handler
     * @return void
     */
    public function stringable($class, $handler = null)
    {
        if ($class instanceof Closure) {
            [$class, $handler] = [$this->firstClosureParameterType($class), $class];
        }

        $this->echoHandlers[$class] = $handler;
    }

    /**
     * Apply the echo handler for the value if it exists.
     *
     * @param  $value  object
     * @return string
     */
    public function stringifyObject($value)
    {
        if (isset($this->echoHandlers[get_class($value)])) {
            return call_user_func($this->echoHandlers[get_class($value)], $value);
        }

        return $value instanceof Htmlable ? $value : (string) $value;
    }
}
