<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Database\DatabaseTransactionsManager as BaseManager;

class DatabaseTransactionsManager extends BaseManager
{
    /**
     * Number of connections transacting on tests to be skiped and run the callbacks correctly.
     */
    protected int $connectionsTransacting = 1;

    /**
     * @param  int  $connectionsTransacting  Number of root connections transacting on tests (to skip for callbacks).
     */
    public function __construct(int $connectionsTransacting = 1)
    {
        parent::__construct();

        $this->connectionsTransacting = $connectionsTransacting;
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
        if ($this->callbackApplicableTransactions()->count() === 0) {
            return $callback();
        }

        $this->pendingTransactions->last()->addCallback($callback);
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->pendingTransactions->skip($this->connectionsTransacting)->values();
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
}
