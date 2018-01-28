<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Column
 *
 * @property-read string $type
 * @property-read string $name
 * @property-read string $after
 * @property-read string $comment
 * @property-read mixed $default
 * @property-read bool $first
 * @property-read bool $nullable
 * @property-read string $storedAs
 * @property-read string $virtualAs
 * @property-read string|true $primary
 * @property-read string|true $unique
 * @property-read string|true $index
 * @property-read string|true $spatialIndex
 */
class Column
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $after;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var mixed
     */
    private $default;

    /**
     * @var bool
     */
    private $first = false;

    /**
     * @var bool
     */
    private $nullable = false;

    /**
     * @var string
     */
    private $storedAs;

    /**
     * @var string
     */
    private $virtualAs;

    /**
     * @var bool
     */
    private $change = false;

    /**
     * @var string|true
     */
    private $primary;

    /**
     * @var string|true
     */
    private $unique;

    /**
     * @var string|true
     */
    private $index;

    /**
     * @var string|true
     */
    private $spatialIndex;

    /**
     * Column constructor.
     *
     * @param string $type
     * @param string $name
     */
    public function __construct(string $type, string $name)
    {
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->{$name};
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        // make all properties read only
        throw new \RuntimeException("Trying to write to read only property '$name'");
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->{$name});
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param string $column
     * @return Column
     */
    public function after(string $column): Column
    {
        $this->after = $column;
        return $this;
    }

    /**
     * @param string $comment
     * @return Column
     */
    public function comment(string $comment): Column
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @param mixed $default
     * @return Column
     */
    public function default($default): Column
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return Column
     */
    public function first(): Column
    {
        $this->first = true;
        return $this;
    }

    /**
     * @param bool $value
     * @return Column
     */
    public function nullable(bool $value = true): Column
    {
        $this->nullable = $value;
        return $this;
    }

    /**
     * @param string $expression
     * @return Column
     */
    public function storedAs(string $expression): Column
    {
        $this->storedAs = $expression;
        return $this;
    }

    /**
     * @param string $expression
     * @return Column
     */
    public function virtualAs(string $expression): Column
    {
        $this->virtualAs = $expression;
        return $this;
    }

    /**
     * @return Column
     */
    public function change(): Column
    {
        $this->change = true;
        return $this;
    }

    /**
     * @param null|string $name
     * @return Column
     */
    public function primary(?string $name = null): Column
    {
        $this->primary = is_null($name) ? true : $name;
        return $this;
    }

    /**
     * @param null|string $name
     * @return Column
     */
    public function unique(?string $name = null): Column
    {
        $this->unique = is_null($name) ? true : $name;
        return $this;
    }

    /**
     * @param null|string $name
     * @return Column
     */
    public function index(?string $name = null): Column
    {
        $this->index = is_null($name) ? true : $name;
        return $this;
    }

    /**
     * @param null|string $name
     * @return Column
     */
    public function spatialIndex(?string $name = null): Column
    {
        $this->spatialIndex = is_null($name) ? true : $name;
        return $this;
    }
}
