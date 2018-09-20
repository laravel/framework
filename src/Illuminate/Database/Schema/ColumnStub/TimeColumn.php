<?php

namespace Illuminate\Database\Schema\ColumnStub;

abstract class TimeColumn extends CommonColumn
{
    /**
     * @param int $precision
     * @return static
     */
    abstract public function precision(int $precision = 0);

    /**
     * @param bool $useCurrent
     * @return static
     */
    abstract public function useCurrent(bool $useCurrent = true);
}
