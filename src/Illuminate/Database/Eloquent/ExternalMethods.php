<?php namespace Illuminate\Database\Eloquent;

use Closure;

trait ExternalMethods
{

    /**
     * The methods provided by external packages
     *
     * @var array
     */
    public static $externalMethods = [];

    /**
     * Override Model's __call() Method;
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (isset(static::$externalMethods[$method])) {
            $closure = static::$externalMethods[$method];

            //it's needed for escape conflicts with possible used "$this" in closure
            $model = $this;

            $closure = Closure::bind($closure, $model, static::class);

            return call_user_func_array($closure, $parameters);
        }

        //Just keep old behavior
        if (in_array($method, ['increment', 'decrement'])) {
            return call_user_func_array([$this, $method], $parameters);
        }

        $query = $this->newQuery();

        return call_user_func_array([$query, $method], $parameters);
    }

    /**
     * Just add external method
     *
     * @param string $name
     * @param Closure $function
     *
     * @return void
     */
    public static function addMethod($name, Closure $function)
    {
        static::$externalMethods[$name] = $function;
    }
    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {

        $attribute = parent::getAttribute($key);

        // If attrubute with this key already exists
        // we just return this attribute
        if (!is_null($attribute))
        {
            return $attribute;
        }

        //But else we need to call external method;
        if (array_key_exists($key, static::$externalMethods))
        {
            return $this->getRelationshipFromMethod($key);
        }
    }
}


