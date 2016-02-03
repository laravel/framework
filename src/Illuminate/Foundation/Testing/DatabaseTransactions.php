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
        foreach ($this->connectionsToTransact() as $name) {
            $this->transacting[$name] = $this->app->make('db')->connection($name);
            $this->transacting[$name]->beginConnection();
        }

        $this->beforeApplicationDestroyed(function () {
            foreach ($this->connectionsToTransact() as $name) {
                $this->app->make('db')->connection($name)->rollBack();
            }
        });
    }
}
