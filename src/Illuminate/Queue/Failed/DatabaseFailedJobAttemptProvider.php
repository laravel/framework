<?php

namespace Illuminate\Queue\Failed;

use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseFailedJobAttemptProvider implements FailedJobAttemptProviderInterface
{
    /**
     * The connection resolver implementation.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The database connection name.
     *
     * @var string
     */
    protected $database;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database failed job provider.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $database
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver, $database, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
        $this->database = $database;
    }

    /**
     * Log a failed job attempt into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  Exception  $exception
     * @return void
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = Carbon::now();
        $message = "{$exception->getMessage()} at {$exception->getFile()} at line {$exception->getLine()}";
        $stack_trace = $exception->getTraceAsString();

        $this->getTable()->insert(compact('connection', 'queue', 'message', 'stack_trace', 'payload', 'failed_at'));
    }

    /**
     * Flush all of the failed job attempts from storage.
     *
     * @return void
     */
    public function flush()
    {
        $this->getTable()->delete();
    }

    /**
     * Get a new query builder instance for the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->resolver->connection($this->database)->table($this->table);
    }
}
