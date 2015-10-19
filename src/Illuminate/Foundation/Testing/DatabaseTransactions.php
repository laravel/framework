<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * @before
     */
    public function beginDatabaseTransaction()
    {
        $db = $this->app->make('db');
        $names = array_keys($this->app['config']['database.connections']);

        foreach ($names as $name) {
            $db->connection($name)->beginTransaction();
        }

        $this->beforeApplicationDestroyed(function () {
            $db = $this->app->make('db');
            $names = array_keys($this->app['config']['database.connections']);

            foreach ($names as $name) {
                $db->connection($name)->rollBack();
            }
        });
    }
}
