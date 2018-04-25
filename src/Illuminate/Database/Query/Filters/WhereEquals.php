<?php

namespace Illuminate\Database\Query\Filters;

class WhereEquals extends Where
{
    public function __construct(string $column, $value)
    {
        parent::__construct($column, '=', $value);
    }
}
