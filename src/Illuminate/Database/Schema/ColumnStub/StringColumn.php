<?php

namespace Illuminate\Database\Schema\ColumnStub;


abstract class StringColumn extends CommonColumn
{
    /**
     * @param int $length
     * @return static
     */
    abstract function length(int $length = 255);
}
