<?php

namespace Illuminate\Database\Concerns;

use Closure;
use Exception;
use Throwable;

trait ManagesTransactions
{
    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction();

            // We'll simply execute the given callback within a try / catch block and if we
            // catch any exception we can rollback this transaction so that none of this
            // gets actually persisted to a database or stored in a permanent fashion.
            try {
                $callbackResult = $callback($this);
            }

            // If we catch an exception we'll rollback this transaction and try again if we
            // are not out of attempts. If we are out of attempts we will just throw the
            // exception back out and let the developer handle an uncaught exceptions.
            catch (Exception $e) {
                $this->handleTransactionException(
                    $e, $currentAttempt, $attempts
                );

                continue;
            } catch (Throwable $e) {
                $this->rollBack();

                throw $e;
            }

            try {
                $this->commit();
            } catch (Exception $e) {
                $this->handleCommitTransactionException(
                    $e, $currentAttempt, $attempts
                );

                continue;
            }

            return $callbackResult;
        }
    }

    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * @param  \Exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     * @return void
     *
     * @throws \Exception
     */
    protected function handleTransactionException($e, $currentAttempt, $maxAttempts)
    {
        // On a deadlock, MySQL rolls back the entire transaction so we can't just
        // retry the query. We have to throw this exception all the way out and
        // let the developer handle it in another way. We will decrement too.
        if ($this->causedByConcurrencyError($e) &&
            $this->transactions > 1) {
            $this->transactions--;

            throw $e;
        }

        // If there was an exception we will rollback this transaction and then we
        // can check if we have exceeded the maximum attempt count for this and
        // if we haven't we will return and try this query again in our loop.
        $this->rollBack();

        if ($this->causedByConcurrencyError($e) &&
            $currentAttempt < $maxAttempts) {
            return;
        }

        throw $e;
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function beginTransaction()
    {
        $this->createTransaction();

        $this->transactions++;

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Create a transaction within the database.
     *
     * @return void
     */
    protected function createTransaction()
    {
        if ($this->transactions == 0) {
            $this->reconnectIfMissingConnection();

            try {
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                $this->handleBeginTransactionException($e);
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->createSavepoint();
        }
    }

    /**
     * Create a save point within the database.
     *
     * @return void
     */
    protected function createSavepoint()
    {
        $this->getPdo()->exec(
            $this->queryGrammar->compileSavepoint('trans'.($this->transactions + 1))
        );
    }

    /**
     * Handle an exception from a transaction beginning.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleBeginTransactionException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->reconnect();

            $this->pdo->beginTransaction();
        } else {
            throw $e;
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }

    /**
     * Handle an exception encountered when committing a transaction.
     *
     * @param  \Exception  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     * @return void
     */
    protected function handleCommitTransactionException($e, $currentAttempt, $maxAttempts)
    {
        $this->transactions--;

        if ($this->causedByConcurrencyError($e) &&
            $currentAttempt < $maxAttempts) {
            return;
        }

        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;
        }

        throw $e;
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  int|null  $toLevel
     * @return void
     *
     * @throws \Exception
     */
    public function rollBack($toLevel = null)
    {
        // We allow developers to rollback to a certain transaction level. We will verify
        // that this given transaction level is valid before attempting to rollback to
        // that level. If it's not we will just return out and not attempt anything.
        $toLevel = is_null($toLevel)
                    ? $this->transactions - 1
                    : $toLevel;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        // Next, we will actually perform this rollback within this database and fire the
        // rollback event. We will also set the current transaction level to the given
        // level that was passed into this method so it will be right from here out.
        try {
            $this->performRollBack($toLevel);
        } catch (Exception $e) {
            $this->handleRollBackException($e);
        }

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');
    }

    /**
     * Perform a rollback within the database.
     *
     * @param  int  $toLevel
     * @return void
     */
    protected function performRollBack($toLevel)
    {
        if ($toLevel == 0) {
            $this->getPdo()->rollBack();
        } elseif ($this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepointRollBack('trans'.($toLevel + 1))
            );
        }
    }

    /**
     * Handle an exception from a rollback.
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleRollBackException($e)
    {
        if ($this->causedByLostConnection($e)) {
            $this->transactions = 0;
        }

        throw $e;
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }
}
