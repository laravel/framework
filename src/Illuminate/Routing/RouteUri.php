<?php

namespace Illuminate\Routing;

class RouteUri
{
    /**
     * Registered field types and their associated regular expressions.
     *
     * @var array
     */
    protected static $typeExpressions = [
        'int' => '[0-9]+',
        'alpha' => '[a-zA-Z]+',
        'alnum' => '[a-zA-Z0-9]+',
        'uuid' => '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}',
    ];

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
     * The regular expression requirements.
     *
     * @var array
     */
    public $wheres = [];

    /**
     * Create a new route URI instance.
     *
     * @param  string  $uri
     * @param  array  $bindingFields
     * @return void
     */
    public function __construct(string $uri, array $bindingFields = [], array $wheres = [])
    {
        $this->uri = $uri;
        $this->bindingFields = $bindingFields;
        $this->wheres = $wheres;
    }

    /**
     * Parse the given URI.
     *
     * @param  string  $uri
     * @return static
     */
    public static function parse($uri)
    {
        $pattern = '/{(?:'.static::typesPattern().'\s+)?(\w+)(?::(\w+))?(\??)}/';

        $bindingFields = [];
        $wheres = [];

        $uri = preg_replace_callback($pattern, function($match) use (&$bindingFields, &$wheres) {
            [$_, $type, $parameter, $field, $optional] = $match;

            if ('' !== $type) {
                $wheres[$parameter] = static::$typeExpressions[$type];
            }

            if ('' !== $field) {
                $bindingFields[$parameter] = $field;
            }

            return '{'.$parameter.$optional.'}';
        }, $uri);

        return new static($uri, $bindingFields, $wheres);
    }

    /**
     * Add a named type and its associated regular expression.
     *
     * @param  string  $name
     * @param  string  $expression
     * @return void
     */
    public static function addType($name, $expression)
    {
        static::$typeExpressions[$name] = $expression;
    }

    /**
     * Get the regular expression for a named type.
     *
     * @param  string  $name
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getExpressionForType($name)
    {
        if (isset(static::$typeExpressions[$name])) {
            return static::$typeExpressions[$name];
        }

        throw new \InvalidArgumentException("No expression for type '$name' has been registered.");
    }

    /**
     * Compile all the named types into a regular expression pattern.
     *
     * @param  string  $delimiter
     * @return string
     */
    protected static function typesPattern($delimiter = '/')
    {
        $quote = function ($type) use ($delimiter) {
            return preg_quote($type, $delimiter);
        };

        $types = array_map($quote, array_keys(static::$typeExpressions));

        return '('.implode('|', $types).')';
    }
}
