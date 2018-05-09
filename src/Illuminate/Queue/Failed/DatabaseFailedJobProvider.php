<?php

namespace Illuminate\Queue\Failed;

use Illuminate\Support\Carbon;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseFailedJobProvider implements FailedJobProviderInterface
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

    protected $query;

    protected $filters = [
                            'queue'             => ['filter_name' => 'queue', 'operator' => '='],
                            'id_from'           => ['filter_name' => 'id', 'operator' => '>='],
                            'id_to'             => ['filter_name' => 'id', 'operator' => '<'],
                            'failed_at_from'    => ['filter_name' => 'failed_at', 'operator' => '>='],
                            'failed_at_to'      => ['filter_name' => 'failed_at', 'operator' => '<'],
                        ];

    protected $filtrationOptions = ['queue', 'id_from', 'id_to', 'failed_at_from', 'failed_at_to'];

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
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = Carbon::now();

        $exception = (string) $exception;

        return $this->getTable()->insertGetId(compact(
            'connection', 'queue', 'payload', 'exception', 'failed_at'
        ));
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        return $this->getTable()->orderBy('id', 'desc')->get()->all();
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return object|null
     */
    public function find($id)
    {
        return $this->getTable()->find($id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * Flush all of the failed jobs from storage.
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

    public function filter($options)
    {
        $this->query = $this->getTable();
        foreach ($options as $option => $value) {
            $this->query = $this->query->where($this->filters[$option]['filter_name'], $this->filters[$option]['operator'], $value);
        }

        return $this;
    }

    public function get()
    {
        return $this->query->orderBy('id', 'desc')->get();
    }

    public function getFiltrationOptions()
    {
        return $this->filtrationOptions;
    }
}
