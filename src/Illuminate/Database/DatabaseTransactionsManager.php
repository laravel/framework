<?php

namespace Illuminate\Database;

class DatabaseTransactionsManager
{
    /**
     * All of the recorded transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $transactions;

    /**
     * The database transaction that should be ignored by callbacks.
     *
     * @var \Illuminate\Database\DatabaseTransactionRecord|null
     */
    protected $callbacksShouldIgnore;

    /**
     * The callback to determine after commit callback should be executed.
     *
     * @var (callable():bool)|null
     */
    protected $afterCommitCallbacksShouldBeExecutedCallback;

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
     * @param  int  $level
     * @return void
     */
    public function commit($connection, $level = 1)
    {
        [$forThisConnection, $forOtherConnections] = $this->transactions->partition(
            fn ($transaction) => $transaction->connection == $connection
        );

        // If the transaction level being commited reaches 1 (meaning it was the root
        // transaction), we'll run the callbacks. In test mode, since we wrap each
        // test in a transaction, we'll run the callbacks when reaching level 2.
        if ($this->afterCommitCallbacksShouldBeExecuted($level)) {
            $this->transactions = $forOtherConnections->values();

            $forThisConnection->map->executeCallbacks();

            if ($this->transactions->isEmpty()) {
                $this->callbacksShouldIgnore = null;
            }
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
        if ($this->transactions->isEmpty() || ($this->callbackApplicableTransactions()->count() == 1)) {
            $callback();
        } else {
            $this->transactions->last()->addCallback($callback);
        }
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
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->transactions->reject(
            fn ($transaction) => $transaction === $this->callbacksShouldIgnore
        )->values();
    }

    /**
     * Add custom callback to determine if after commit callbacks should be executed.
     *
     * @param  (callable(int, \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>):(bool))|null  $callback
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecutedUsing(callable $callback = null)
    {
        $this->afterCommitCallbacksShouldBeExecutedCallback = $callback;

        return $this;
    }

    /**
     * Determine if after commit callbacks should be executed.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        return is_callable($this->afterCommitCallbacksShouldBeExecutedCallback)
            ? call_user_func($this->afterCommitCallbacksShouldBeExecutedCallback, $level, $this->transactions)
            : $level === 1;
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
