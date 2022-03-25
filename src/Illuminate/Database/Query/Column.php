<?php

namespace Illuminate\Database\Query;

use RuntimeException;

/**
 * @method static \Illuminate\Database\Query\Column max(string|Column $name)
 * @method static \Illuminate\Database\Query\Column min(string|Column $name)
 * @method static \Illuminate\Database\Query\Column sum(string|Column $name)
 * @method static \Illuminate\Database\Query\Column avg(string|Column $name)
 * @method static \Illuminate\Database\Query\Column date(string|Column $name)
 */
class Column
{
    /**
     * @var \Illuminate\Database\Query\Column|\Illuminate\Database\Query\Expression|string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $alias = null;

    /**
     * @var string|null
     */
    protected $function = null;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string[]
     */
    protected static $basicFunctions = [
        'min',
        'max',
        'sum',
        'avg',
        'date',
    ];

    /**
     * @param  \Illuminate\Database\Query\Column|\Illuminate\Database\Query\Expression|string  $name
     * @param  string|null  $function
     * @param  array  $parameters
     * @return void
     */
    public function __construct($name, $function = null, $parameters = [])
    {
        $this->name = $name;
        $this->function = $function ? strtolower($function) : null;
        $this->parameters = $parameters;
    }

    /**
     * @param  string  $name
     * @param  array  $arguments
     * @return \Illuminate\Database\Query\Column
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($name, $arguments)
    {
        if (! in_array($name, static::$basicFunctions)) {
            throw new RuntimeException('Cannot find a basic database function with the name '.$name);
        }

        return new self($arguments[0], $name);
    }

    /**
     * @param  \Illuminate\Database\Query\Expression|string  $name
     * @return static
     */
    public static function name($name): self
    {
        return new self($name);
    }

    /**
     * @param  \Illuminate\Database\Query\Expression|string  $name
     * @return static
     */
    public static function count($name = '*'): self
    {
        return new self($name, 'count');
    }

    /**
     * @param  \Illuminate\Database\Query\Column|\Illuminate\Database\Query\Expression|string  ...$columns
     * @return static
     */
    public static function coalesce(...$columns): self
    {
        return new self(array_shift($columns), 'coalesce', $columns);
    }

    /**
     * @param  \Illuminate\Database\Query\Column|\Illuminate\Database\Query\Expression|string  ...$columns
     * @return static
     */
    public static function concat(...$columns): self
    {
        return new self(array_shift($columns), 'concat', $columns);
    }

    /**
     * @param  string|null  $alias
     * @return $this
     */
    public function as($alias = null)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return \Illuminate\Database\Query\Column|\Illuminate\Database\Query\Expression|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string|null
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
