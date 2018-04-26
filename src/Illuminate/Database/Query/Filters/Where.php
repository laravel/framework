<?php

namespace Illuminate\Database\Query\Filters;

use Illuminate\Database\Query\Builder;

class Where
{
    /**
     * Column name.
     *
     * @var string
     */
    protected $column;

    /**
     * The value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * The clause operator.
     *
     * @var null
     */
    protected $operator;

    /**
     * @var string
     */
    protected $boolean;

    /**
     * Create a new callable object instance.
     *
     * @param  $column
     * @param  null  $operator
     * @param  null  $value
     * @param  string  $boolean
     */
    public function __construct($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
        $this->boolean = $boolean;
    }

    /**
     * Add a basic where clause to then query.
     *
     * @param  Builder  $builder
     * @return Builder
     */
    public function __invoke($builder)
    {
        return $builder->where($this->column, $this->operator, $this->value, $this->boolean);
    }
}
