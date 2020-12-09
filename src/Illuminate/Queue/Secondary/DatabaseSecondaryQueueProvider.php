<?php

namespace Illuminate\Queue\Secondary;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\InteractsWithTime;

class DatabaseSecondaryQueueProvider implements SecondaryQueueProviderInterface
{
    use InteractsWithTime;

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
     * {@inheritdoc}
     */
    public function push($connection, $queue, $job, $delay, $exception)
    {
        $failed_at = Date::now()->getTimestamp();

        $due_at = $delay ? $this->availableAt($delay) : $failed_at;

        $exception = (string) $exception;

        return $this->getTable()->insertGetId(compact(
            'connection', 'queue', 'job', 'exception', 'failed_at', 'due_at'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->getTable()->orderBy('id', 'asc')->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function forget($id)
    {
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * {@inheritdoc}
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
