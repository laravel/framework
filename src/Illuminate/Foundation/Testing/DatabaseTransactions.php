<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * This property will store references to the connections currently affected by the transaction. They are stored
     * as a key => value array of connection_name => ConnectionObject.
     *
     * @var array
     */
    protected $transactingConnections = [];

    /**
     * Get the names of the database connections that should be included in the transaction. The default value of empty
     * string will cause only the default database connection to be included in the transaction.
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

        foreach ($this->connectionsToTransact() as $connectionName) {
            $connection = $db->connection($connectionName);
            $this->transactingConnections[$connectionName] = $connection;

            $connection->beginTransaction();
        }

        $this->beforeApplicationDestroyed(function () {
            $db = $this->app->make('db');

            foreach ($this->connectionsToTransact() as $connectionName) {
                $db->connection($connectionName)->rollBack();
            }
        });
    }
}
