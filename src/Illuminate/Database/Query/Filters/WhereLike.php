<?php

namespace Illuminate\Database\Query\Filters;

class WhereLike extends Where
{
    public function __construct(string $column, $value, bool $includeBefore = true, bool $includeAfter = true)
    {
        parent::__construct($column, 'like', sprintf(
            '%s%s%s',
            $includeBefore ? '%' : '',
            $value,
            $includeAfter ? '%' : ''
        ));
    }
}
