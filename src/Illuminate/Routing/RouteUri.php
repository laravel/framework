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
     * @return void
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

        $uri = preg_replace_callback('/{(\w+)(?::(\w+))?(\??)}/', function ($match) use (&$bindingFields) {
            [$_, $parameter, $field, $optional] = $match;

            if ('' !== $field) {
                $bindingFields[$parameter] = $field;
            }

            return '{'.$parameter.$optional.'}';
        }, $uri);

        return new static($uri, $bindingFields);
    }
}
