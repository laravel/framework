<?php

namespace Illuminate\Database\Console\Seeds;

trait WithDatabaseTransaction
{
    /**
     * If database transaction should be used.
     *
     * @return bool
     */
    protected function useDatabaseTransaction()
    {
        return true;
    }

    /**
     * Wraps the seeder call in a database transaction.
     *
     * @param  callable  $callback
     * @return callable
     */
    public function withDatabaseTransaction(callable $callback)
    {
        if (isset($this->container)) {
            return fn() => $this->container->make('db.connection')->transaction($callback);
        }

        return $callback;
    }
}
