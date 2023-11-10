<?php

namespace Illuminate\Database;

use Illuminate\Support\Collection;

class DatabaseTransactionsManager
{
    /**
     * All of the committed transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $committedTransactions;

    /**
     * All of the pending transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $pendingTransactions;

    /**
     * Create a new database transactions manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->committedTransactions = new Collection;
        $this->pendingTransactions = new Collection;
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
        $this->pendingTransactions->push(
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
        $this->pendingTransactions = $this->pendingTransactions->reject(
            fn ($transaction) => $transaction->connection == $connection && $transaction->level > $level
        )->values();
    }

    /**
     * Commit the active database transaction.
     *
     * @param  string  $connection
     * @return void
     */
    public function commit($connection)
    {
        [$forThisConnection, $forOtherConnections] = $this->committedTransactions->partition(
            fn ($transaction) => $transaction->connection == $connection
        );

        $this->committedTransactions = $forOtherConnections->values();

        $forThisConnection->map->executeCallbacks();
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
     * Move all the pending transactions to a committed state.
     *
     * @param  string  $connection
     * @return void
     */
    public function stageTransactions($connection)
    {
        $this->committedTransactions = $this->committedTransactions->merge(
            $this->pendingTransactions->filter(fn ($transaction) => $transaction->connection === $connection)
        );

        $this->pendingTransactions = $this->pendingTransactions->reject(
            fn ($transaction) => $transaction->connection === $connection
        );
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->pendingTransactions;
    }

    /**
     * Determine if after commit callbacks should be executed for the given transaction level.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        return $level === 0;
    }

    /**
     * Get all of the pending transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPendingTransactions()
    {
        return $this->pendingTransactions;
    }

    /**
     * Get all of the committed transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCommittedTransactions()
    {
        return $this->committedTransactions;
    }
}
