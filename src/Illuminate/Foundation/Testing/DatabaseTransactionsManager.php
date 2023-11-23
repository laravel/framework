<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Database\DatabaseTransactionsManager as BaseManager;

class DatabaseTransactionsManager extends BaseManager
{
    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        // When running in testing mode, the baseline transaction level is 1. If the
        // current transaction level is 1, it means we have no transactions except
        // the wrapping one. In that case, we execute the callback immediately.
        if ($this->currentlyBeingExecutedTransaction->level === 1) {
            return $callback();
        }

        $this->currentlyBeingExecutedTransaction?->addCallback($callback);
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
