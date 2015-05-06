<?php namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * @before
     */
    public function beginDatabaseTransaction()
    {
        $this->app->make('db')->beginTransaction();
    }

    /**
     * @after
     */
    public function rollBackTransaction()
    {
        $this->app->make('db')->rollBack();
    }
}
