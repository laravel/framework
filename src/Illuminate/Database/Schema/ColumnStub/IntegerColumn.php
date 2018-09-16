<?php

namespace Illuminate\Database\Schema\ColumnStub;


abstract class IntegerColumn extends CommonColumn
{
    /**
     * @param bool $autoIncrement
     * @return static
     */
    abstract function autoIncrement(bool $autoIncrement = true);

    /**
     * @param bool $unsigned
     * @return static
     */
    abstract function unsigned(bool $unsigned = true);
}
