<?php

namespace Illuminate\Database\Schema\ColumnStub;


abstract class IndexColumn extends CommonColumn
{
    /**
     * @param string $algorithm
     * @return static
     */
    abstract function algorithm(string $algorithm = null);

    /**
     * @param string|array $columns
     * @return static
     */
    abstract function columns($columns);
}
