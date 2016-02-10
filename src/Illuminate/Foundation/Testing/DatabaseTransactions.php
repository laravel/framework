<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * Retrieve the names of the database connections that should be included in the transaction.
     *
     * The default value of empty string will cause only the default database connection to be included in the transaction.
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return [''];
    }

    public function beginDatabaseTransaction()
    {
        $db = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $db->connection($name)->beginTransaction();
        }

        $this->beforeApplicationDestroyed(function () {
            $db = $this->app->make('db');
            foreach ($this->connectionsToTransact() as $name) {
                $db->connection($name)->rollBack();
            }
        });
    }
}
