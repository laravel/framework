<?php

namespace Illuminate\Foundation\Testing;

class DatabaseTransactionsManager extends \Illuminate\Database\DatabaseTransactionsManager
{
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

        $this->transactions->last()->addCallback($callback);
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->transactions->skip(1)->values();
    }

    /**
     * Determine if after commit callbacks should be executed.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        return $level === 2;
    }
}
