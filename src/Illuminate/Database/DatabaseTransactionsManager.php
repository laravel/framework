<?php

namespace Illuminate\Database;

class DatabaseTransactionsManager
{
    /**
     * All of the recorded transactions.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $transactions;

    /**
     * The database transaction that should be ignored by callbacks.
     *
     * @var \Illuminate\Database\DatabaseTransactionRecord
     */
    protected $callbacksShouldIgnore;

    /**
     * Create a new database transactions manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transactions = collect();
    }

    /**
     * Start a new database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return void
     */
    public function begin($connection, $level)
    {
        $this->transactions->push(
            new DatabaseTransactionRecord($connection, $level)
        );
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return void
     */
    public function rollback($connection, $level)
    {
        $this->transactions = $this->transactions->reject(
            fn ($transaction) => $transaction->connection == $connection && $transaction->level > $level
        )->values();

        if ($this->transactions->isEmpty()) {
            $this->callbacksShouldIgnore = null;
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @param  string  $connection
     * @return void
     */
    public function commit($connection)
    {
        [$forThisConnection, $forOtherConnections] = $this->transactions->partition(
            fn ($transaction) => $transaction->connection == $connection
        );

        $this->transactions = $forOtherConnections->values();

        $forThisConnection->map->executeCallbacks();

        if ($this->transactions->isEmpty()) {
            $this->callbacksShouldIgnore = null;
        }
    }

    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        if ($current = $this->callbackApplicableTransactions()->last()) {
            return $current->addCallback($callback);
        }

        $callback();
    }

    /**
     * Specify that callbacks should ignore the given transaction when determining if they should be executed.
     *
     * @param  \Illuminate\Database\DatabaseTransactionRecord  $transaction
     * @return $this
     */
    public function callbacksShouldIgnore(DatabaseTransactionRecord $transaction)
    {
        $this->callbacksShouldIgnore = $transaction;

        return $this;
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection
     */
    public function callbackApplicableTransactions()
    {
        return $this->transactions->reject(function ($transaction) {
            return $transaction === $this->callbacksShouldIgnore;
        })->values();
    }

    /**
     * Get all the transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }
}
