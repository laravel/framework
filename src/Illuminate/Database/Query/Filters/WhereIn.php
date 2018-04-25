<?php

namespace Illuminate\Database\Query\Filters;

class WhereIn
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
        return $builder->whereIn($this->column, $this->value);
    }
}
