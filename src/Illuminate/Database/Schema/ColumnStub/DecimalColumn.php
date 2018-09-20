<?php

namespace Illuminate\Database\Schema\ColumnStub;

abstract class DecimalColumn extends CommonColumn
{
    /**
     * @param int $places
     * @return static
     */
    abstract public function places(int $places = null);

    /**
     * @param int|null $total
     * @return static
     */
    abstract public function total(int $total = null);

    /**
     * @param bool $unsigned
     * @return static
     */
    abstract public function unsigned(bool $unsigned = true);
}
