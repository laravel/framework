<?php

namespace Illuminate\Database\Query\Filters;

class WhereNotEquals extends Where
{
    public function __construct(string $column, $value)
    {
        parent::__construct($column, '!=', $value);
    }
}
