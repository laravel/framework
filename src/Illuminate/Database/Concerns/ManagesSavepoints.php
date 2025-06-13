<?php

namespace Illuminate\Database\Concerns;

use Illuminate\Database\Events\SavepointCreated;
use Illuminate\Database\Events\SavepointReleased;
use Illuminate\Database\Events\SavepointRolledBack;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

trait ManagesSavepoints
{
    /**
     * Status of savepoint management initialization.
     */
    protected bool $savepointManagementInitialized = false;

    /**
     * An array of savepoints indexed by transaction level.
     *
     * @var array<int, array<string>>
     */
    protected array $savepoints = [];

    /**
     * @var string Prefix used for savepoint names.
     */
    protected string $savepointPrefix = '__savepoint__';

    /**
     * Determine if the connection supports savepoints.
     */
    public function supportsSavepoints(): bool
    {
        return $this->queryGrammar->supportsSavepoints();
    }

    /**
     * Determine if the connection supports releasing savepoints.
     */
    public function supportsSavepointRelease(): bool
    {
        return $this->queryGrammar->supportsSavepointRelease();
    }

    /**
     * Create a savepoint within the current transaction. Optionally provide a callback
     * to be executed following creation of the savepoint. If the callback fails, the transaction
     * will be rolled back to the savepoint. The savepoint will be released after the callback
     * has been executed.
     *
     * @throws Throwable
     */
    public function savepoint(string $name, ?callable $callback = null): mixed
    {
        if (! $this->supportsSavepoints()) {
            $this->savepointsUnsupportedError();
        }

        if (! $this->transactionLevel()) {
            $this->savepointOutsideTransactionError();
        }

        if ($this->hasSavepoint($name)) {
            $this->duplicateSavepointError($name);
        }

        if ($this->getPdo()->exec($this->queryGrammar->compileSavepoint($this->wrapSavepointName($name))) === false) {
            $this->savepointActionFailedError();
        }

        $this->savepoints[$this->transactionLevel()] ??= [];

        $this->savepoints[$this->transactionLevel()][] = $this->wrapSavepointName($name);

        $this->event(new SavepointCreated($this, $name));

        if (! is_null($callback)) {
            try {
                return $callback();
            } catch (Throwable $e) {
                if ($this->hasSavepoint($name)) {
                    $this->rollbackToSavepoint($name);
                }

                throw $e;
            } finally {
                if ($this->supportsSavepointRelease() && $this->hasSavepoint($name)) {
                    $this->releaseSavepoint($name);
                }
            }
        }

        return true;
    }

    /**
     * Rollback to a named savepoint within the current transaction.
     *
     * @throws Throwable
     */
    public function rollbackToSavepoint(string $name): void
    {
        if (! $this->supportsSavepoints()) {
            $this->savepointsUnsupportedError();
        }

        if (! $this->hasSavepoint($name)) {
            $this->unknownSavepointError($name);
        }

        $name = $this->wrapSavepointName($name);

        if ($this->getPdo()->exec($this->queryGrammar->compileRollbackToSavepoint($name)) === false) {
            $this->savepointActionFailedError();
        }

        if (($position = array_search($name, $this->savepoints[$this->transactionLevel()], true)) !== false) {
            $released = array_slice(
                $this->savepoints[$this->transactionLevel()],
                $position + 1,
                count($this->savepoints[$this->transactionLevel()]) - $position,
                true
            );

            $this->savepoints[$this->transactionLevel()] = array_slice(
                $this->savepoints[$this->transactionLevel()],
                0,
                $position + 1,
                true
            );
        }

        $this->event(
            new SavepointRolledBack(
                $this, $this->unwrapSavepointName($name),
                array_map(function ($name) {
                    return $this->unwrapSavepointName($name);
                }, $released ?? [])
            )
        );
    }

    /**
     * Release a savepoint from the current transaction.
     *
     * @throws Throwable
     */
    public function releaseSavepoint(string $name, ?int $level = null): void
    {
        if (! $this->supportsSavepoints()) {
            $this->savepointsUnsupportedError();
        }

        if (! $this->supportsSavepointRelease()) {
            $this->savepointReleaseUnsupportedError();
        }

        if (! $this->hasSavepoint($name)) {
            $this->unknownSavepointError($name);
        }

        $name = $this->wrapSavepointName($name);

        if ($this->getPdo()->exec($this->queryGrammar->compileReleaseSavepoint($name)) === false) {
            $this->savepointActionFailedError();
        }

        $this->savepoints[$level ?? $this->transactionLevel()] =
            array_values(array_diff($this->savepoints[$level ?? $this->transactionLevel()], [$name]));

        $this->event(new SavepointReleased($this, $this->unwrapSavepointName($name)));
    }

    /**
     * Purge all savepoints from the current transaction.
     *
     * @throws Throwable
     */
    public function purgeSavepoints(?int $level = null): void
    {
        if (! $this->supportsSavepoints()) {
            $this->savepointsUnsupportedError();
        }

        if (! $this->supportsSavepointRelease()) {
            $this->savepointPurgeUnsupportedError();
        }

        foreach ($this->savepoints[$level ?? $this->transactionLevel()] ?? [] as $name) {
            $this->releaseSavepoint($this->unwrapSavepointName($name), $level);
        }
    }

    /**
     * Determine if the connection has a savepoint within the current transaction.
     */
    public function hasSavepoint(string $name): bool
    {
        return in_array($this->wrapSavepointName($name), $this->savepoints[$this->transactionLevel()] ?? [], true);
    }

    /**
     * Get the names of all savepoints within the current transaction.
     *
     * @throws Throwable
     */
    public function getSavepoints(): array
    {
        if (! $this->supportsSavepoints()) {
            $this->savepointsUnsupportedError();
        }

        return array_map(function ($name) {
            return $this->unwrapSavepointName($name);
        }, $this->savepoints[$this->transactionLevel()] ?? []);
    }

    /**
     * Get the name of the current savepoint.
     */
    public function getCurrentSavepoint(): ?string
    {
        return isset($this->savepoints[$this->transactionLevel()])
            ? $this->unwrapSavepointName(end($this->savepoints[$this->transactionLevel()]))
            : null;
    }

    /**
     * Initialize savepoint management for the connection; sets up event
     * listeners to manage savepoints during transaction events.
     */
    protected function initializeSavepointManagement(bool $force = false): void
    {
        if (! $this->supportsSavepoints()) {
            return;
        }

        if ($this->savepointManagementInitialized && ! $force) {
            return;
        }

        $this->savepoints = [];

        $this->events?->listen(function (TransactionBeginning $event) {
            $this->syncTransactionBeginning();
        });

        $this->events?->listen(function (TransactionCommitted $event) {
            $this->syncTransactionCommitted();
        });

        $this->events?->listen(function (TransactionRolledBack $event) {
            $this->syncTransactionRolledBack();
        });

        $this->savepointManagementInitialized = true;
    }

    /**
     * Update savepoint management to reflect the transaction beginning event.
     */
    protected function syncTransactionBeginning(): void
    {
        $this->savepoints[$this->transactionLevel()] = [];
    }

    /**
     * Update savepoint management to reflect the transaction committed event.
     */
    protected function syncTransactionCommitted(): void
    {
        $this->syncSavepoints();
    }

    /**
     * Update savepoint management to reflect the transaction rolled back event.
     */
    protected function syncTransactionRolledBack(): void
    {
        $this->syncSavepoints();
    }

    /**
     * Sync savepoints after a transaction commit or rollback.
     *
     * @throws Throwable
     */
    protected function syncSavepoints(): void
    {
        foreach (array_keys($this->savepoints) as $level) {
            if ($level > $this->transactionLevel()) {
                if ($this->supportsSavepointRelease()) {
                    $this->purgeSavepoints($level);
                }

                unset($this->savepoints[$level]);
            }
        }

        if (! $this->transactionLevel()) {
            $this->savepoints = [];
        }

        $this->savepoints[$this->transactionLevel() ?: 0] = [];
    }

    /**
     * Wrap a savepoint name with the savepoint prefix.
     */
    protected function wrapSavepointName(string $name): string
    {
        return $this->savepointPrefix.$name;
    }

    /**
     * Unwrap a savepoint name from the savepoint prefix.
     */
    protected function unwrapSavepointName(string $name): string
    {
        return substr($name, strlen($this->savepointPrefix));
    }

    /**
     * Throw an error indicating that savepoints are unsupported.
     *
     * @throws RuntimeException
     */
    protected function savepointsUnsupportedError(): void
    {
        throw new RuntimeException(
            'This database connection does not support creating savepoints.'
        );
    }

    /**
     * Throw an error indicating that releasing savepoints is unsupported.
     *
     * @throws RuntimeException
     */
    protected function savepointReleaseUnsupportedError(): void
    {
        throw new RuntimeException(
            'This database connection does not support releasing savepoints.'
        );
    }

    /**
     * Throw an error indicating that purging savepoints is unsupported.
     *
     * @throws RuntimeException
     */
    protected function savepointPurgeUnsupportedError(): void
    {
        throw new RuntimeException(
            'This database connection does not support purging savepoints.'
        );
    }

    /**
     * Throw an error indicating that a savepoint already exists with the given name.
     *
     * @throws InvalidArgumentException
     */
    protected function duplicateSavepointError(string $name): void
    {
        throw new InvalidArgumentException(
            "Savepoint '{$name}' already exists."
        );
    }

    /**
     * Throw an error indicating that the specified savepoint does not exist.
     *
     * @throws InvalidArgumentException
     */
    protected function unknownSavepointError(string $name): void
    {
        throw new InvalidArgumentException(
            "Savepoint '{$name}' does not exist."
        );
    }

    /**
     * Throw an error indicating that a savepoint cannot be created outside a transaction.
     *
     * @throws LogicException
     */
    protected function savepointOutsideTransactionError(): void
    {
        throw new LogicException(
            'Cannot create savepoint outside of transaction.'
        );
    }

    /**
     * Throw an error indicating that an error occurred while executing a savepoint action.
     *
     * @throws RuntimeException
     */
    protected function savepointActionFailedError(): void
    {
        throw new RuntimeException(
            'An error occurred while executing savepoint action.'
        );
    }
}
