<?php

namespace Illuminate\Database\Query;

class CommonTableExpression
{
    /**
     * The name of the CTE it can be referenced by.
     *
     * @var string
     */
    public $name;
    /**
     * The Builder this CTE belongs to.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    public $query;
    /**
     * The optional explicit columns to reference.
     *
     * @var string[]
     */
    public $columns;
    /**
     * Whether this is a `WITH RECURSIVE` CTE or not
     *
     * @var bool
     */
    public $recursive;

    /**
     * Construct a new CTE from the given primitives.
     *
     * @param string $name
     * @param \Illuminate\Database\Query\Builder $query
     * @param string[] $columns
     * @param bool $recursive
     */
    public function __construct($name, $query, $columns = [], $recursive = false)
    {
        $this->name = $name;
        $this->query = $query;
        $this->columns = $columns;
        $this->recursive = $recursive;
    }
}
