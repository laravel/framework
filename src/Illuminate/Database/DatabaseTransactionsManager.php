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
    protected $afterCommitCallbacksRunningInTestTransaction = false;

    /**
     * Create a new database transactions manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transactions = collect();
        $this->afterCommitCallbacksRunningInTestTransaction = false;
    }

    /**
     * Sets the transaction manager to test mode.
     *
     * @return self
     */
    public function withAfterCommitCallbacksInTestTransactionAwareMode()
    {
        $this->afterCommitCallbacksRunningInTestTransaction = true;

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
    public function commit($connection, $level = 1)
    {
        // If the transaction level being commited reaches 1 (meaning it was the root
        // transaction), we'll run the callbacks. In test mode, since we wrap each
        // test in a transaction, we'll run the callbacks when reaching level 2.
        if ($level == 1 || ($this->afterCommitCallbacksRunningInTestTransaction && $level == 2)) {
            [$forThisConnection, $forOtherConnections] = $this->transactions->partition(
                fn ($transaction) => $transaction->connection == $connection
            );

            $this->transactions = $forOtherConnections->values();

            $forThisConnection->map->executeCallbacks();
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
        // If there are no transactions, we'll run the callbacks right away. Also, we'll run it
        // right away when we're in test mode and we only have the wrapping transaction. For
        // every other case, we'll queue up the callback to run after the commit happens.
        if ($this->transactions->isEmpty() || ($this->afterCommitCallbacksRunningInTestTransaction && $this->transactions->count() == 1)) {
            return $callback();
        }

        return $this->transactions->last()->addCallback($callback);
    }

    /**
     * Specify that callbacks should ignore the given transaction when determining if they should be executed.
     *
     * @param  \Illuminate\Database\DatabaseTransactionRecord  $transaction
     * @return $this
     *
     * @deprecated Will be removed in a future Laravel version. Use withAfterCommitCallbacksInTestTransactionAwareMode() instead.
     */
    public function callbacksShouldIgnore(DatabaseTransactionRecord $transaction)
    {
        // This method was meant for testing only, so we're forwarding the call to the new method...
        return $this->withAfterCommitCallbacksInTestTransactionAwareMode();
    }

        /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection
     *
     * @deprecated Will be removed in a future Laravel version.
     */
    public function callbackApplicableTransactions()
    {
        if (! $this->afterCommitCallbacksRunningInTestTransaction) {
            return clone $this->transactions;
        }

        return $this->transactions->skip(1)->values();
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
