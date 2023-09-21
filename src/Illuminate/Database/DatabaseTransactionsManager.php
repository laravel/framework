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
     * When in test mode, we'll run the after commit callbacks on the top-level transaction.
     *
     * @var bool
     */
    protected $callbacksTransactionManagerTestMode = false;

    /**
     * Create a new database transactions manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transactions = collect();
        $this->callbacksTransactionManagerTestMode = false;
    }

    /**
     * Sets the transaction manager to test mode.
     *
     * @return self
     */
    public function withCallbacksExecutionInTestMode()
    {
        $this->callbacksTransactionManagerTestMode = true;

        return $this;
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
    }

    /**
     * Commit the active database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return void
     */
    public function commit($connection, $level)
    {
        // If the transaction level being commited reaches 1 (meaning it was the root
        // transaction), we'll run the callbacks. In test mode, since we wrap each
        // test in a transaction, we'll run the callbacks when reaching level 2.
        if ($level == 1 || ($this->isRunningInTestMode() && $level == 2)) {
            [$forThisConnection, $forOtherConnections] = $this->transactions->partition(
                fn ($transaction) => $transaction->connection == $connection
            );

            $this->transactions = $forOtherConnections->values();

            $forThisConnection->map->executeCallbacks();
        }
    }

    /**
     * Checks if the transaction manager is running in test mode.
     *
     * @return bool
     */
    public function isRunningInTestMode()
    {
        return $this->callbacksTransactionManagerTestMode;
    }

    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        if ($this->transactions->isEmpty() || ($this->isRunningInTestMode() && $this->transactions->count() == 1)) {
            return $callback();
        }

        return $this->transactions->last()->addCallback($callback);
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
