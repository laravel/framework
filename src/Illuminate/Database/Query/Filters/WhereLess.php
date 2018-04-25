<?php

namespace Illuminate\Database\Query\Filters;

class WhereLess extends Where
{
    public function __construct(string $column, $value, bool $include = false)
    {
        parent::__construct($column, $include ? '<=' : '<', $value);
    }
}
