<?php

namespace Illuminate\Routing;

class RouteUri
{
    /**
     * The route URI.
     *
     * @var string
     */
    public $uri;

    /**
     * The fields that should be used when resolving bindings.
     *
     * @var array
     */
    public $bindingFields = [];

    /**
     * Create a new route URI instance.
     *
     * @param  string  $uri
     * @param  array  $bindingFields
     */
    public function __construct(string $uri, array $bindingFields = [])
    {
        $this->uri = $uri;
        $this->bindingFields = $bindingFields;
    }

    /**
     * Parse the given URI.
     *
     * @param  string  $uri
     * @return static
     */
    public static function parse($uri)
    {
        $bindingFields = [];

        $uri = preg_replace_callback('/\{([\w:]+?)\??}/', function ($match) use (&$bindingFields) {
            if (! str_contains($match[0], ':')) {
                return $match[0];
            }

            $segments = explode(':', trim($match[0], '{}?'));

            $bindingFields[$segments[0]] = $segments[1];

            return str_contains($match[0], '?')
                ? '{'.$segments[0].'?}'
                : '{'.$segments[0].'}';
        }, $uri);

        return new static($uri, $bindingFields);
    }
}
