<?php

namespace Illuminate\Database\PDO;

use PDO;
use PDOStatement;

/**
 * @mixin \PDOStatement
 */
class Statement
{
    public function __construct(
        protected PDOStatement $statement,
    ) {
        //
    }

    /**
     * Get the underlying PDOStatement for the result.
     *
     * @return \PDOStatement
     */
    public function statement(): PDOStatement
    {
        return $this->statement;
    }

    /**
     * Fetch the next row from a result set.
     *
     * @param \Illuminate\Database\PDO\Mode|null $mode
     * @return mixed
     */
    public function fetch(Mode $mode = null): mixed
    {
        return $this->statement->fetch(...($mode ? $mode->arguments() : []));
    }

    /**
     * Fetch all the rows from a result set.
     *
     * @param \Illuminate\Database\PDO\Mode|null $mode
     * @return array|false
     */
    public function fetchAll(Mode $mode = null): array|false
    {
        return $this->statement->fetchAll(...($mode ? $mode->arguments() : []));
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  array  $bindings
     * @return void
     */
    public function bindValues(array $bindings): self
    {
        foreach ($bindings as $key => $value) {
            $this->statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR
                },
            );
        }

        return $this;
    }

    /**
     * Route undefined functions to the underlying PDO statement.
     *
     * @param string $method
     * @param mixed $parameters
     * @return mixed
     */
    public function __call($method, $parameters): mixed
    {
        return $this->statement->$method(...$parameters);
    }
}
