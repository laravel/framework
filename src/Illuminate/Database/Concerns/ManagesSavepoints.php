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
     * Determine if the connection supports savepoints.
     */
    public function supportsSavepoints(): bool
    {
        return $this->queryGrammar?->supportsSavepoints() ?? false;
    }

    /**
     * Determine if the connection supports releasing savepoints.
     */
    public function supportsSavepointRelease(): bool
    {
        return $this->queryGrammar?->supportsSavepointRelease() ?? false;
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

        if ($this->getPdo()->exec($this->queryGrammar->compileSavepoint($this->encodeSavepointName($name))) === false) {
            $this->savepointActionFailedError('create', $name);
        }

        $this->savepoints[$this->transactionLevel()][] = $name;

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

        if (($position = array_search($name, $this->savepoints[$level = $this->transactionLevel()] ?? [], true)) !== false) {
            $released = array_splice($this->savepoints[$level], $position + 1);
        }

        if ($this->getPdo()->exec($this->queryGrammar->compileRollbackToSavepoint($this->encodeSavepointName($name))) === false) {
            $this->savepointActionFailedError('rollback to', $name);
        }

        $this->event(new SavepointRolledBack($this, $name, $released ?? []));
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

        if ($this->getPdo()->exec($this->queryGrammar->compileReleaseSavepoint($this->encodeSavepointName($name))) === false) {
            $this->savepointActionFailedError('release', $name);
        }

        $this->savepoints[$level ??= $this->transactionLevel()] = array_values(array_diff($this->savepoints[$level], [$name]));

        $this->event(new SavepointReleased($this, $name));
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
            $this->releaseSavepoint($name, $level);
        }
    }

    /**
     * Determine if the connection has a savepoint within the current transaction.
     */
    public function hasSavepoint(string $name): bool
    {
        return in_array($name, $this->savepoints[$this->transactionLevel()] ?? [], true);
    }

    /**
     * Get the names of all savepoints within the current transaction.
     */
    public function getSavepoints(): array
    {
        return $this->savepoints[$this->transactionLevel()] ?? [];
    }

    /**
     * Get the name of the current savepoint.
     */
    public function getCurrentSavepoint(): ?string
    {
        return isset($this->savepoints[$level = $this->transactionLevel()]) && ! empty($this->savepoints[$level])
            ? end($this->savepoints[$level])
            : null;
    }

    /**
     * Initialize savepoint management for the connection; sets up event
     * listeners to manage savepoints during transaction events.
     */
    protected function initializeSavepointManagement(bool $force = false): void
    {
        if (($this->savepointManagementInitialized && ! $force) || ! $this->supportsSavepoints()) {
            return;
        }

        $this->savepointManagementInitialized = true;

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
     *
     * @throws Throwable
     */
    protected function syncTransactionCommitted(): void
    {
        $this->syncSavepoints();
    }

    /**
     * Update savepoint management to reflect the transaction rolled back event.
     *
     * @throws Throwable
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
    }

    /**
     * Encode a savepoint name to ensure it's safe for SQL compilation.
     */
    protected function encodeSavepointName(string $name): string
    {
        return bin2hex($name);
    }

    /**
     * Throw an error indicating that savepoints are unsupported.
     *
     * @throws RuntimeException
     */
    protected function savepointsUnsupportedError(): void
    {
        throw new RuntimeException('This database connection does not support creating savepoints.');
    }

    /**
     * Throw an error indicating that releasing savepoints is unsupported.
     *
     * @throws RuntimeException
     */
    protected function savepointReleaseUnsupportedError(): void
    {
        throw new RuntimeException('This database connection does not support releasing savepoints.');
    }

    /**
     * Throw an error indicating that purging savepoints is unsupported.
     *
     * @throws RuntimeException
     */
    protected function savepointPurgeUnsupportedError(): void
    {
        throw new RuntimeException('This database connection does not support purging savepoints.');
    }

    /**
     * Throw an error indicating that a savepoint already exists with the given name.
     *
     * @throws InvalidArgumentException
     */
    protected function duplicateSavepointError(string $name): void
    {
        throw new InvalidArgumentException(
            "Savepoint '{$name}' already exists at position "
            .array_search($name, $this->savepoints[$this->transactionLevel()] ?? [], true)
            ." in transaction level {$this->transactionLevel()}. "
            ."Use a different name or call rollbackToSavepoint('{$name}') first. "
            ."Current savepoints: ['".implode("', '", $this->savepoints[$this->transactionLevel()] ?? [])."']."
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
            "Savepoint '{$name}' does not exist in transaction level {$this->transactionLevel()}."
            .(empty($this->savepoints[$this->transactionLevel()] ?? [])
                ? ' No savepoints exist at this transaction level.'
                : " Available savepoints: ['".implode("', '", $this->savepoints[$this->transactionLevel()])."'].")
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
            'Cannot create savepoint outside of transaction. Current transaction level: 0. '
            .'Call beginTransaction() first or use the transaction() helper method.'
        );
    }

    /**
     * Throw an error indicating that an error occurred while executing a savepoint action.
     *
     * @throws RuntimeException
     */
    protected function savepointActionFailedError(string $action = 'execute', string $name = ''): void
    {
        throw new RuntimeException(
            "Failed to {$action} savepoint".($name ? " '{$name}'" : '')
            .'. Check database permissions and transaction state. '
            ."Current transaction level: {$this->transactionLevel()}."
        );
    }
}
