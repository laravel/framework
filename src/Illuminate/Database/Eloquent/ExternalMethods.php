<?php namespace Illuminate\Database\Eloquent;

use Closure;

trait ExternalMethods
{

    /**
     * The methods provided by external packages
     *
     * @var Closure[]
     */
    protected static $externalMethods = [];

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

            //it's needed for escape conflicts with
            // possible used "$this" in closure
            $model = $this;

            // We bind this context to the closure as $model.
            // Then we can use it like
            //
            // ```
            // Entity::addMethod('someScope', function($param){
            //
            //     return $model->whereParam($param);
            // });
            // ```
            //  or
            //
            // ```
            // Entity::addMethod('relation', function(){
            //
            //     return $model->hasMany(\App\SomeOther::class);
            // });
            // ```
            //
            // This methods can be used in serviceProviders, out of Entity context.
            // It extend understanding idea of IoC paradigm
            //
            $closure = Closure::bind(static::$externalMethods[$method], $model, static::class);

            return call_user_func_array($closure, $parameters);
        }

        // For keep old behavior
        parent::__call($method, $parameters);
    }

    /**
     * Just add an external method
     *
     * @param string $name
     * @param Closure $method

     * @return void
     */
    public static function addMethod($name, Closure $method)
    {
        static::$externalMethods[$name] = $method;
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

        // If attribute with this key already exists
        // we return this attribute
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


