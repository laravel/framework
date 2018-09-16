<?php

namespace Illuminate\Database\Schema\ColumnStub;


abstract class DecimalColumn extends CommonColumn
{
    /**
     * @param int $places
     * @return static
     */
    abstract function places(int $places = null);

    /**
     * @param int $total
     * @return static
     */
    abstract function total(int $total = null);

    /**
     * @param bool $unsigned
     * @return static
     */
    abstract function unsigned(bool $unsigned = true);
}
