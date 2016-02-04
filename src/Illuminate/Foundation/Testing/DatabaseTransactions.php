<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * The connections currently affected by the transaction. Indexed by connection name.
     *
     * @var array
     */
    protected $transacting = [];

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
            $this->transacting[$name] = $db->connection($name);
            $this->transacting[$name]->beginConnection();
        }

        $this->beforeApplicationDestroyed(function () {
            $db = $this->app->make('db');
            foreach ($this->connectionsToTransact() as $name) {
                $db->connection($name)->rollBack();
            }
        });
    }
}
