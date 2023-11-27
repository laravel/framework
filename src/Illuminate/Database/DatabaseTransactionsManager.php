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
     * The transaction currently being executed.
     *
     * @var \Illuminate\Database\DatabaseTransactionRecord|null
     */
    protected $currentlyBeingExecutedTransaction = null;

    /**
     * The transactions that have been executed.
     *
     * @var Collection<int, \Illuminate\Database\DatabaseTransactionRecord[]>
     */
    protected $transactions = [];

    public function __construct()
    {
        $this->transactions = new Collection();
    }

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
        }

        $this->transactions[] = $newTransaction;

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

        if ($this->afterCommitCallbacksShouldBeExecuted($currentTransaction->level)) {
            $currentTransaction->executeCallbacks();
        }
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  string  $connection
     * @return void
     */
    public function rollback($connection, $newTransactionLevel)
    {
        $transaction = $this->currentTransaction[$connection];
        $transactionIndex = $this->transactions->search($transaction);
        // Find the last committed transaction previous to the one that has been rolled back.
        // WIP: maybe use a linked list here?
        $lastTransaction = $this->transactions
            ->filter(fn ($transaction, $foundIndex) => $transaction->committed === false && $foundIndex < $transactionIndex)
            ->last();

        $this->removeTransaction($transaction);

        // In a nested setting, the rolled back transaction isn't necessarily in the same
        // connection as the parent transaction. That's why we move the pointer to
        // the last transaction before the one that has been rolled back.
        $this->movePointersTo($connection, $lastTransaction);
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
     * Get all of the pending transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPendingTransactions()
    {
        return $this->transactions
            ->filter(fn ($transaction) => $transaction->committed === false)
            ->values();
    }

    /**
     * Get all of the committed transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCommittedTransactions()
    {
        return $this->transactions
            ->filter(fn ($transaction) => $transaction->committed === true)
            ->values();
    }

    /**
     * Move the pointer to the given transaction.
     *
     * @param string $connection
     * @param \Illuminate\Database\DatabaseTransactionRecord|null $transaction
     * @return void
     */
    protected function movePointersTo($connection, $transaction)
    {
        $this->currentTransaction[$connection] = $transaction;
        $this->currentlyBeingExecutedTransaction = $transaction;
    }

    /**
     * Remove a given transaction from the ledger.
     *
     * @param \Illuminate\Database\DatabaseTransactionRecord $transaction
     * @return void
     */
    protected function removeTransaction($transaction)
    {
        $transaction->resetCallbacks();
        $transaction->resetChildren();
        $transaction->parent?->removeChild($transaction);

        $this->transactions = $this->transactions->reject(fn ($t) => $t === $transaction);
    }
}
