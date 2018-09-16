<?php

namespace Illuminate\Database\Schema\ColumnStub;


abstract class CommonColumn
{
    /**
     * @param bool $change
     * @return static
     */
    abstract function change(bool $change = true);

    /**
     * @param bool $index
     * @return static
     */
    abstract function index(bool $index = true);

    /**
     * @param string $name
     * @return static
     */
    abstract function name(string $name);

    /**
     * @param bool $nullable
     * @return static
     */
    abstract function nullable(bool $nullable = true);

    /**
     * @param string $type
     * @return static
     */
    abstract function type(string $type);

    /**
     * @param bool $unique
     * @return static
     */
    abstract function unique(bool $unique = true);
}
