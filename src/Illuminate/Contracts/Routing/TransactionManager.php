<?php

namespace Illuminate\Contracts\Routing;

interface TransactionManager
{
    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollback();
}
