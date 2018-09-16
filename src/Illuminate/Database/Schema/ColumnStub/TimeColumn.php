<?php

namespace Illuminate\Database\Schema\ColumnStub;


abstract class TimeColumn extends CommonColumn
{
    /**
     * @param int $precision
     * @return static
     */
    abstract function precision(int $precision = 0);
}
