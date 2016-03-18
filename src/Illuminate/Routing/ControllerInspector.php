<?php

namespace Illuminate\Routing;

use ReflectionClass;
use ReflectionMethod;

class ControllerInspector
{
    /**
     * An array of HTTP verbs.
     *
     * @var array
     */
    protected $verbs = [
        'any', 'get', 'post', 'put', 'patch',
        'delete', 'head', 'options',
    ];

    /**
     * Get the routable methods for a controller.
     *
     * @param  string  $controller
     * @param  string  $prefix
     * @return array
     */
    public function getRoutable($controller, $prefix)
    {
        $routable = [];

        $reflection = new ReflectionClass($controller);

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        // To get the routable methods, we will simply spin through all methods on the
        // controller instance checking to see if it belongs to the given class and
        // is a publicly routable method. If so, we will add it to this listings.
        foreach ($methods as $method) {
            if ($this->isRoutable($method)) {
                $data = $this->getMethodData($method, $prefix);

                $routable[$method->name][] = $data;

                // If the routable method is an index method, we will create a special index
                // route which is simply the prefix and the verb and does not contain any
                // the wildcard place-holders that each "typical" routes would contain.
                if ($data['plain'] == $prefix.'/index') {
                    $routable[$method->name][] = $this->getIndexData($data, $prefix);
                }
            }
        }

        return $routable;
    }

    /**
     * Determine if the given controller method is routable.
     *
     * @param  \ReflectionMethod  $method
     * @return bool
     */
    public function isRoutable(ReflectionMethod $method)
    {
        if ($method->class == 'Illuminate\Routing\Controller') {
            return false;
        }

        return starts_with($method->name, $this->verbs);
    }

    /**
     * Get the method data for a given method.
     *
     * @param  \ReflectionMethod  $method
     * @param  string  $prefix
     * @return array
     */
    public function getMethodData(ReflectionMethod $method, $prefix)
    {
        $verb = $this->getVerb($name = $method->name);

        $uri = $this->addUriWildcards($plain = $this->getPlainUri($name, $prefix));

        return compact('verb', 'plain', 'uri');
    }

    /**
     * Get the routable data for an index method.
     *
     * @param  array   $data
     * @param  string  $prefix
     * @return array
     */
    protected function getIndexData($data, $prefix)
    {
        return ['verb' => $data['verb'], 'plain' => $prefix, 'uri' => $prefix];
    }

    /**
     * Extract the verb from a controller action.
     *
     * @param  string  $name
     * @return string
     */
    public function getVerb($name)
    {
        return head(explode('_', snake_case($name)));
    }

    /**
     * Determine the URI from the given method name.
     *
     * @param  string  $name
     * @param  string  $prefix
     * @return string
     */
    public function getPlainUri($name, $prefix)
    {
        return $prefix.'/'.implode('-', array_slice(explode('_', snake_case($name)), 1));
    }

    /**
     * Add wildcards to the given URI.
     *
     * @param  string  $uri
     * @return string
     */
    public function addUriWildcards($uri)
    {
        return $uri.'/{one?}/{two?}/{three?}/{four?}/{five?}';
    }
}
