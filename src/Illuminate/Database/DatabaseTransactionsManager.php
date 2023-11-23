<?php

namespace Illuminate\Database;

use Illuminate\Support\Collection;

class DatabaseTransactionsManager
{
    /**
     * The current transactions.
     *
     * @var array<string, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $currentTransaction = [];

    /**
     * The root transactions.
     *
     * @var array <string, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $rootTransaction = [];

    /**
     * The transaction currently being executed.
     *
     * @var \Illuminate\Database\DatabaseTransactionRecord|null
     */
    protected $currentlyBeingExecutedTransaction = null;

    /**
     * Start a new database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return \Illuminate\Database\DatabaseTransactionRecord
     */
    public function begin($connection, $level)
    {
        $newTransaction = new DatabaseTransactionRecord(
            $connection,
            $level,
        );

        if (isset($this->currentTransaction[$connection])) {
            $this->currentTransaction[$connection]->addChild($newTransaction);
        } else {
            $this->rootTransaction[$connection] = $newTransaction;
        }

        $this->movePointersTo($connection, $newTransaction);

        return $newTransaction;
    }

    /**
     * Commit the root database transaction and execute callbacks.
     *
     * @param  string  $connection
     * @return void
     */
    public function commit($connection)
    {
        $currentTransaction = $this->currentTransaction[$connection];
        $currentTransaction->commit();

        $this->movePointersTo($connection, $this->currentTransaction[$connection]->parent);

        if (! $this->afterCommitCallbacksShouldBeExecuted($currentTransaction->level)) {
            return;
        }

        $currentTransaction?->executeCallbacks();
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  string  $connection
     * @return void
     */
    public function rollback($connection)
    {
        $this->currentlyBeingExecutedTransaction?->resetCallbacks();
        $this->currentlyBeingExecutedTransaction?->resetChildren();

        $this->getParentTransaction($connection)?->removeChild($this->currentlyBeingExecutedTransaction);
        $this->movePointersTo($connection, $this->currentTransaction[$connection]->parent);
    }

    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        if ($current = $this->currentlyBeingExecutedTransaction) {
            return $current->addCallback($callback);
        }

        $callback();
    }

    /**
     * Determine if after commit callbacks should be executed for the given transaction level.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        return $level === 1;
    }

    /**
     * Get the current transaction for the given connection.
     *
     * @param string $connection
     * @return \Illuminate\Database\DatabaseTransactionRecord|null
     */
    protected function currentTransaction($connection)
    {
        return $this->currentTransaction[$connection];
    }

    /**
     * Get the parent transaction for the current connection.
     *
     * @param string $connection
     * @return \Illuminate\Database\DatabaseTransactionRecord|null
     */
    protected function getParentTransaction($connection)
    {
        return $this->currentTransaction($connection)->parent;
    }

    /**
     * Move the pointer to the given transaction.
     *
     * @param string $connection
     * @param \Illuminate\Database\DatabaseTransactionRecord $transaction
     * @return void
     */
    public function movePointersTo($connection, $transaction)
    {
        $this->currentTransaction[$connection] = $transaction;
        $this->currentlyBeingExecutedTransaction = $transaction;
    }
}
