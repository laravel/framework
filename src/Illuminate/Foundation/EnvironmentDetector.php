<?php

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EnvironmentDetector
{
    /**
     * Detect the application's current environment.
     *
     * @param  \Closure  $callback
     * @param  array|null  $consoleArgs
     * @return string
     */
    public function detect(Closure $callback, $consoleArgs = null)
    {
        if ($consoleArgs) {
            return $this->detectConsoleEnvironment($callback, $consoleArgs);
        }

        return $this->detectWebEnvironment($callback);
    }

    /**
     * Set the application environment for a web request.
     *
     * @param  \Closure  $callback
     * @return string
     */
    protected function detectWebEnvironment(Closure $callback)
    {
        return call_user_func($callback);
    }

    /**
     * Set the application environment from command-line arguments.
     *
     * @param  \Closure  $callback
     * @param  array  $args
     * @return string
     */
    protected function detectConsoleEnvironment(Closure $callback, array $args)
    {
        // First we will check if an environment argument was passed via console arguments
        // and if it was that automatically overrides as the environment. Otherwise, we
        // will check the environment as a "web" request like a typical HTTP request.
        if (!is_null($value = $this->getEnvironmentArgument($args))) {
            return head(array_slice(explode('=', $value), 1));
        }

        return $this->detectWebEnvironment($callback);
    }

    /**
     * Get the environment argument from the console.
     *
     * @param  array  $args
     * @return string|null
     */
    protected function getEnvironmentArgument(array $args)
    {
        return Arr::first($args, function ($k, $v) {
            return Str::startsWith($v, '--env');
        });
    }
}
