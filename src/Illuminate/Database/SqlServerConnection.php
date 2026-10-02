<?php

namespace Illuminate\Database;

use Closure;
use Exception;
use Illuminate\Database\Query\Grammars\SqlServerGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\SqlServerProcessor;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as SchemaGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use Illuminate\Filesystem\Filesystem;
use PDOException;
use RuntimeException;
use Throwable;

class SqlServerConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'SQL Server';
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @template TReturn
     *
     * @param  (\Closure(static): TReturn)  $callback
     * @param  int  $attempts
     * @return TReturn
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($a = 1; $a <= $attempts; $a++) {
            if ($this->getDriverName() === 'sqlsrv') {
                return parent::transaction($callback, $attempts);
            }

            $this->getPdo()->exec('BEGIN TRAN');

            // We'll simply execute the given callback within a try / catch block
            // and if we catch any exception we can rollback the transaction
            // so that none of the changes are persisted to the database.
            try {
                $result = $callback($this);

                $this->getPdo()->exec('COMMIT TRAN');
            }

            // If we catch an exception, we will rollback so nothing gets messed
            // up in the database. Then we'll re-throw the exception so it can
            // be handled how the developer sees fit for their applications.
            catch (Throwable $e) {
                $this->getPdo()->exec('ROLLBACK TRAN');

                throw $e;
            }

            return $result;
        }
    }

    /**
     * Handle an exception encountered when running a transacted statement.
     *
     * @param  \Throwable  $e
     * @param  int  $currentAttempt
     * @param  int  $maxAttempts
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
    {
        try {
            parent::handleTransactionException($e, $currentAttempt, $maxAttempts);
        } catch (Throwable $exception) {
            // If SQL Server already rolled back the entire transaction, the failed savepoint rollback
            // is only a symptom. We will re-throw the exception that actually caused the rollback.
            if ($exception !== $e && $this->transactions === 0 && $this->causedByMissingSavepoint($exception)) {
                throw $e;
            }

            throw $exception;
        }
    }

    /**
     * Handle an exception from a rollback.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleRollBackException(Throwable $e)
    {
        // Some errors, such as an error raised within a trigger, a MERGE that updates the same row
        // twice, or any error while XACT_ABORT is on, make SQL Server roll back the entire
        // transaction instead of just the failing statement, which discards every savepoint.
        // Laravel's transaction level must be reset so later transactions really commit.
        if ($this->transactions > 1 && $this->causedByMissingSavepoint($e)) {
            $this->transactions = 0;

            // The driver begins a new transaction after the rollback, so we will end that one too.
            if ($this->getPdo()->inTransaction()) {
                $this->getPdo()->rollBack();
            }

            $this->transactionsManager?->rollback(
                $this->getName(), $this->transactions
            );
        }

        parent::handleRollBackException($e);
    }

    /**
     * Determine if the given exception was caused by rolling back to a savepoint that no longer exists.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function causedByMissingSavepoint(Throwable $e)
    {
        // 3903: The ROLLBACK TRANSACTION request has no corresponding BEGIN TRANSACTION.
        // 6401: Cannot roll back %.*ls. No transaction or savepoint of that name was found.
        return $e instanceof PDOException
            && in_array((int) ($e->errorInfo[1] ?? 0), [3903, 6401], true);
    }

    /**
     * Escape a binary value for safe SQL embedding.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeBinary($value)
    {
        $hex = bin2hex($value);

        return "0x{$hex}";
    }

    /**
     * Determine if the given database exception was caused by a unique constraint violation.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    protected function isUniqueConstraintError(Exception $exception): bool
    {
        return (bool) preg_match('#Cannot insert duplicate key(?: row)? in object#i', $exception->getMessage());
    }

    /**
     * Extract the index that caused a unique constraint violation.
     *
     * @param  Exception  $exception
     * @return array{index: string|null, columns: list<string>}
     */
    protected function parseUniqueConstraintViolation(Exception $exception): array
    {
        $index = null;

        if (preg_match('#with unique index \'([^\']+)\'#i', $message = $exception->getMessage(), $matches)) {
            $index = $matches[1];
        } elseif (preg_match('#Violation of [A-Z ]+ constraint \'([^\']+)\'#i', $message, $matches)) {
            $index = $matches[1];
        }

        return ['columns' => [], 'index' => $index];
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\SqlServerGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\SqlServerBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SqlServerBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\SqlServerGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar($this);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \Illuminate\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     *
     * @throws \RuntimeException
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        throw new RuntimeException('Schema dumping is not supported when using SQL Server.');
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\SqlServerProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SqlServerProcessor;
    }
}
