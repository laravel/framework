<?php

namespace Illuminate\Database;

use Illuminate\Support\Traits\Macroable;
use PDOStatement;

/**
 * @mixin \PDOStatement
 */
class Statement
{
    use Macroable {
        __call as macroCall;
    }

    public function __construct(
        protected PDOStatement $statement,
    ) {
        //
    }

    public function statement(): PDOStatement
    {
        return $this->statement;
    }

    /**
     * @return mixed
     */
    public function fetch(FetchMode $mode = null): mixed
    {
        return $this->statement->fetch(...($mode ? $mode->arguments() : []));
    }

    /**
     * @return array|false
     */
    public function fetchAll(FetchMode $mode = null): array|false
    {
        return $this->statement->fetchAll(...($mode ? $mode->arguments() : []));
    }

    /**
     * @param string $method
     * @param mixed $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->statement->$method(...$parameters);
    }
}
