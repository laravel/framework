<?php

namespace Illuminate\Database;

use Brick\Math\BigInteger;
use Illuminate\Database\Query\Grammars\MariaDbGrammar as QueryGrammar;
use Illuminate\Database\Query\Processors\MariaDbProcessor;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar as SchemaGrammar;
use Illuminate\Database\Schema\MariaDbBuilder;
use Illuminate\Database\Schema\MariaDbSchemaState;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class MariaDbConnection extends MySqlConnection
{
    /**
     * {@inheritdoc}
     */
    public function getDriverTitle()
    {
        return 'MariaDB';
    }

    /**
     * Determine if the connected database is a MariaDB database.
     *
     * @return bool
     */
    public function isMaria()
    {
        return true;
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return Str::between(parent::getServerVersion(), '5.5.5-', '-MariaDB');
    }

    /**
     * Get the ID of the last committed transaction.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getLastCommitId()
    {
        $gtid = $this->usingWritePdo(fn () => $this->selectOne('select @@log_bin as log_bin, @@gtid_binlog_pos as gtid_binlog_pos'));
        if (! $gtid->log_bin) {
            throw new RuntimeException("The MariaDB configuration 'log_bin = ON' is required to retrieve the last commit ID.");
        }

        return $gtid->gtid_binlog_pos;
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

        return implode(',', array_keys($this->parseCommitIds($values)));
    }

    /**
     * Determine if the given commit ID has been applied.
     *
     * @param  string  $value
     * @param  int  $wait
     * @return bool
     */
    public function hasCommitId(string $value, int $wait = 0)
    {
        // MASTER_GTID_WAIT only checks the GTIDs this server received through replication (@@gtid_slave_pos), so it
        // can't find GTIDs the server wrote itself: on a primary, or on a server mixing replicated and own writes (e.g.
        // multi-source replication) a wait would wait for their replicas having the commit applied. That's not what we
        // want here. So a check first with zero wait is used, and @@gtid_current_pos which combines replicated and own
        // GTIDs as a fallback is loaded for a local check on primary servers.
        $check = $this->selectOne('select master_gtid_wait(?, 0) as master_gtid_wait, @@gtid_current_pos as gtid_current_pos', [$value]);
        if ((int) $check->master_gtid_wait === 0) {
            return true;
        }

        $expected = array_column($this->parseCommitIds([$value]), 'sequence', 'domain');
        foreach ($this->parseCommitIds([$check->gtid_current_pos]) as $gtid) {
            if (isset($expected[$gtid['domain']]) && $gtid['sequence']->isGreaterThanOrEqualTo($expected[$gtid['domain']])) {
                unset($expected[$gtid['domain']]);
            }
        }
        if ($expected === [] || $wait <= 0) {
            return $expected === [];
        }

        return (int) $this->scalar('select master_gtid_wait(?, ?)', [$value, $wait]) === 0;
    }

    /**
     * Parse a set of commit IDs (GTIDs) into the newest GTID of every MariaDB replication domain.
     *
     * Every GTID has the structure "{domain}-{server}-{sequence}" and every commit ID can be a comma-separated list
     * of GTIDs. But MariaDB only compares the sequence within a domain, so older GTIDs of a domain are dropped.
     *
     * @param  string[]  $values
     * @return array<string, array{domain: string, sequence: \Brick\Math\BigInteger}>
     *
     * @throws \RuntimeException
     */
    protected function parseCommitIds(array $values)
    {
        $gtids = $parsed = [];
        foreach (explode(',', implode(',', $values)) as $gtid) {
            $gtid = trim($gtid);
            if ($gtid === '') {
                continue;
            }

            if (! preg_match('/^(?<domain>\d+)-(?<server>\d+)-(?<sequence>\d+)$/', $gtid, $matches)) {
                throw new RuntimeException("The commit ID '{$gtid}' is not a valid MariaDB GTID.");
            }

            $domain = $matches['domain'];
            $sequence = BigInteger::of($matches['sequence']); // The sequence is a 64-bit unsigned integer and can exceed PHP's signed integers.
            if (! isset($parsed[$domain]) || $sequence->isGreaterThan($parsed[$domain]['sequence'])) {
                $gtids[$domain] = $gtid;
                $parsed[$domain] = compact('domain', 'sequence');
            }
        }
        ksort($gtids);
        ksort($parsed);

        return array_combine($gtids, $parsed);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\MariaDbGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar($this);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\MariaDbBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MariaDbBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\MariaDbGrammar
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
     * @return \Illuminate\Database\Schema\MariaDbSchemaState
     */
    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null)
    {
        return new MariaDbSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\MariaDbProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MariaDbProcessor;
    }
}
