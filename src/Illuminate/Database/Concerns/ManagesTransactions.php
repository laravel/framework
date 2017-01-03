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
        for ($a = 1; $a <= $attempts; $a++) {
            $this->beginTransaction();

            // We'll simply execute the given callback within a try / catch block
            // and if we catch any exception we can rollback the transaction
            // so that none of the changes are persisted to the database.
            try {
                $result = $callback($this);

                $this->commit();
            }

            // If we catch an exception, we will roll back so nothing gets messed
            // up in the database. Then we'll re-throw the exception so it can
            // be handled how the developer sees fit for their applications.
            catch (Exception $e) {
                if ($this->causedByDeadlock($e) && $this->transactions > 1) {
                    --$this->transactions;

                    throw $e;
                }

                $this->rollBack();

                if ($this->causedByDeadlock($e) && $a < $attempts) {
                    continue;
                }

                throw $e;
            } catch (Throwable $e) {
                $this->rollBack();

                throw $e;
            }

            return $result;
        }
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     * @throws Exception
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0) {
            try {
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                if ($this->causedByLostConnection($e)) {
                    $this->reconnect();
                    $this->pdo->beginTransaction();
                } else {
                    throw $e;
                }
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepoint('trans'.($this->transactions + 1))
            );
        }

        ++$this->transactions;

        $this->fireConnectionEvent('beganTransaction');
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
     * Rollback the active database transaction.
     *
     * @param  int|null  $toLevel
     * @return void
     */
    public function rollBack($toLevel = null)
    {
        if (is_null($toLevel)) {
            $toLevel = $this->transactions - 1;
        }

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        if ($toLevel == 0) {
            $this->getPdo()->rollBack();
        } elseif ($this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepointRollBack('trans'.($toLevel + 1))
            );
        }

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');
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
