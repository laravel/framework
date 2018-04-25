<?php

namespace Illuminate\Database\Query\Filters;

class WhereNotIn
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var mixed
     */
    private $value;

    public function __construct(string $column, $value)
    {
        $this->column = $column;
        $this->value = $value;
    }

    /**
     * @param \Illuminate\Database\Query\Builder $builder
     * @return \Illuminate\Database\Query\Builder
     */
    public function __invoke($builder)
    {
        return $builder->whereNotIn($this->column, $this->value);
    }
}
