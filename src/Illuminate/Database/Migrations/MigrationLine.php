<?php

namespace Illuminate\Database\Migrations;

use Illuminate\Database\Schema\Blueprint;
use InvalidArgumentException;
use ReflectionClass;

class MigrationLine
{
    protected $name;
    protected $method;
    protected $caller;
    protected $chainedMethods;
    protected static $blueprintClassReflection;

    /**
     * @param string  $name
     * @param string  $method
     * @param array  $chainedMethods
     * @param string  $caller
     */
    public function __construct($name, $method, $chainedMethods = [], $caller = '$table')
    {
        $this->name = $name;
        $this->method = $method;
        $this->caller = $caller;
        $this->chainedMethods = $chainedMethods;

        if (is_null(self::$blueprintClassReflection)) {
            self::$blueprintClassReflection = new ReflectionClass(Blueprint::class);
        }
    }

    /**
     * Get the caller property
     *
     * @return string
     */
    public function getCaller() 
    {
        return $this->caller;
    }

    /**
     * Get the name property
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the method property
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Convert the object to string
     *
     * @param  string  $prependWhitespace
     * @param  boolean  $appendLineFeed
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    public function resolve($prependWhitespace = '', $appendLineFeed = true)
    {
        if (!self::$blueprintClassReflection->hasMethod($this->method)) {
            throw new InvalidArgumentException("The method {$this->getMethod()} for column {$this->getName()} is invalid");
        }

        $line = sprintf('%s%s->%s(\'%s\')', $prependWhitespace, $this->caller, $this->method, $this->name);

        collect($this->chainedMethods)->each(function ($method) use (&$line) {
            if (!self::$blueprintClassReflection->hasMethod($method)) {
                throw new InvalidArgumentException("The chained method {$method} for column {$this->getName()} is invalid");
            }

            $line .= sprintf('->%s()', $method);
        });

        $line .= ';';
        if ($appendLineFeed) {
            $line .= PHP_EOL;
        }

        return $line;
    }

    /**
     * convert line to a dropColumn statement using the objects name and caller
     *
     * @param  string  $prependWhitespace
     * @param  boolean  $appendLineFeed
     * @return string
     */
    public function dropColumn($prependWhitespace = '', $appendLineFeed = true)
    {
        $line = sprintf('%s%s->dropColumn(\'%s\');', $prependWhitespace, $this->caller, $this->name);
        if ($appendLineFeed) {
            $line .= PHP_EOL;
        }

        return $line;
    }

    public function __toString()
    {
        return $this->resolve();
    }
}