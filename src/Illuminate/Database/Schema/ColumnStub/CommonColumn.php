<?php

namespace Illuminate\Database\Schema\ColumnStub;

abstract class CommonColumn
{
    /**
     * @param bool $change
     * @return static
     */
    abstract public function change(bool $change = true);

    /**
     * @param bool $index
     * @return static
     */
    abstract public function index(bool $index = true);

    /**
     * @param string $name
     * @return static
     */
    abstract public function name(string $name);

    /**
     * @param bool $nullable
     * @return static
     */
    abstract public function nullable(bool $nullable = true);

    /**
     * @param string $type
     * @return static
     */
    abstract public function type(string $type);

    /**
     * @param bool $unique
     * @return static
     */
    abstract public function unique(bool $unique = true);
}
