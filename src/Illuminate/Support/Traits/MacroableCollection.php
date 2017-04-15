<?php

namespace Illuminate\Support\Traits;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Str;

trait MacroableCollection
{
    /**
     * Checks if macro is registered or if it's a magic macro.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMagicMacro($name)
    {
        return Str::startsWith($name, ['where']);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $macroExists = static::hasMacro($method);
        $magicMacroExists = static::hasMagicMacro($method);

        if (! $macroExists && ! $magicMacroExists) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        if ($macroExists) {
            if (static::$macros[$method] instanceof Closure) {
                return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
            }

            return call_user_func_array(static::$macros[$method], $parameters);
        }

        // Normalise the attribute we're searching for
        $attribute = Str::snake(str_replace('where', '', $method));

        // Add it to the parameters
        array_unshift($parameters, $attribute);

        // Perform the filter
        return call_user_func_array(static::registerMagicWhere()->bindTo($this, static::class), $parameters);
    }

    /**
     * Register the magic where macro.
     *
     * @return \Closure
     */
    protected function registerMagicWhere()
    {
        // Signature:
        // $collection->whereName('jeff');
        // $collection->whereName('!=', 'jeff');
        return function ($attribute, $operator, $value = null) {
            if ($value == null) {
                return $this->where($attribute, '=', $operator);
            }

            return $this->where($attribute, $operator, $value);
        };
    }
}
