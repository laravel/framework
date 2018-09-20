<?php

namespace Illuminate\Database\Schema\ColumnStub;

abstract class FloatColumn extends DecimalColumn
{
    /**
     * @param int $places
     * @return static
     */
    abstract public function places(int $places = 2);

    /**
     * @param int $total
     * @return static
     */
    abstract public function total(int $total = 8);
}
