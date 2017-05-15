<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * @before
     */
    public function beginDatabaseTransaction()
    {
        foreach ($this->app->make('db')->getConnections() as $connection) {
            $connection->beginTransaction();
        }

        $this->beforeApplicationDestroyed(function () {
            foreach ($this->app->make('db')->getConnections() as $connection) {
                $connection->rollBack();
                $connection->disconnect();
            }
        });
    }
}
