<?php

namespace Illuminate\Foundation\Testing;

class DatabaseTransactionsManager extends \Illuminate\Database\DatabaseTransactionsManager
{
    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->transactions->reject(
            fn ($transaction) => $transaction === $this->transactions->first()
        )->values();
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
