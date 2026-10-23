<?php

namespace Illuminate\Database;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Query\Grammars\PostgresGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Illuminate\Database\Schema\PostgresSchemaState;
use Illuminate\Filesystem\Filesystem;
use PDO;
use RuntimeException;

class PostgresConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'PostgreSQL';
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = $this->usesEmulatedPrepares()
                    ? ($value ? 'true' : 'false')
                    : (int) $value;
            }
        }

        return $bindings;
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

        return "'\x{$hex}'::bytea";
    }

    /**
     * Escape a bool value for safe SQL embedding.
     *
     * @param  bool  $value
     * @return string
     */
    protected function escapeBool($value)
    {
        return $value ? 'true' : 'false';
    }

    /**
     * Determine if the given database exception was caused by a unique constraint violation.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    protected function isUniqueConstraintError(Exception $exception)
    {
        return '23505' === $exception->getCode();
    }

    /**
     * Extract the index and columns that caused a unique constraint violation.
     *
     * @param  Exception  $exception
     * @return array{index: string|null, columns: list<string>}
     */
    protected function parseUniqueConstraintViolation(Exception $exception): array
    {
        [$index, $columns] = [null, []];

        if (preg_match('#unique constraint "([^"]+)"#i', $message = $exception->getMessage(), $matches)) {
            $index = $matches[1];
        }

        if (preg_match('#Key \(([^)]+)\)=#i', $message, $matches)) {
            $columns = array_map(trim(...), explode(',', $matches[1]));
        }

        return ['columns' => $columns, 'index' => $index];
    }

    /**
     * Get the ID of the last committed transaction.
     *
     * @return string
     */
    public function getLastCommitId()
    {
        // Some examples recommend using pg_current_wal_insert_lsn(), but it can't be used reliably: When the last WAL
        // record ends exactly at a page boundary, it points behind the header of the next page. This is a WAL position
        // that a replica can't ever reach. The only way to have a replica acknowledging to have received this WAL is
        // when more WAL records are written and that impossible position is superseded by new WAL blocks. So a simple
        // read-after-write couldn't work until some other connection also commits a transaction.
        return $this->usingWritePdo(fn () => $this->scalar('select pg_current_wal_lsn()'));
    }

    /**
     * Calculate the commit ID for a set of commit IDs that includes all of them.
     *
     * @param  string[]  $values
     * @return string
     *
     * @throws \RuntimeException
     */
    public function mergeCommitIds(array $values)
    {
        if ($values === []) {
            throw new RuntimeException("The list of commit IDs can't be empty.");
        }

        // LSNs are linear, so the newest one includes all older ones.
        return $this->scalar('select value::pg_lsn from json_array_elements_text(?) order by 1 desc limit 1', [json_encode($values)]);
    }

    /**
     * Determine if the given commit ID has been applied.
     *
     * @param  string  $value
     * @param  int  $wait
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function hasCommitId(string $value, int $wait = 0)
    {
        if ($wait > 0) {
            if (version_compare($this->getServerVersion(), '19', '<')) {
                throw new RuntimeException('The PostgreSQL version does not support waiting for a commit ID. PostgreSQL 19 or newer is required.');
            }

            $status = $this->scalar("wait for lsn {$this->escapeString($value)} with (timeout {$this->escapeString("{$wait}s")}, no_throw)");

            // A primary can't wait for its own LSNs, the normal wait = 0 check must be used.
            if ($status !== 'not in recovery') {
                return $status === 'success';
            }
        }

        return $this->scalar('select case when pg_is_in_recovery() then pg_last_wal_replay_lsn() else pg_current_wal_insert_lsn() end >= ?::pg_lsn', [$value]);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\PostgresGrammar
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
     * @return \Illuminate\Database\Schema\PostgresSchemaState
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new PostgresSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Determine if the active PDO configuration uses emulated prepares.
     *
     * @return bool
     */
    protected function usesEmulatedPrepares()
    {
        // Binding preparation runs after query routing has selected the PDO variant...
        $config = match ($this->latestReadWriteTypeUsed()) {
            'read' => $this->readPdoConfig,
            'direct' => $this->directPdoConfig,
            default => $this->config,
        };

        return (bool) ($config['options'][PDO::ATTR_EMULATE_PREPARES] ?? false);
    }
}
