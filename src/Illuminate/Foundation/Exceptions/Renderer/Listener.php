<?php

namespace Illuminate\Foundation\Exceptions\Renderer;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;

class Listener
{
    /**
     * The queries that have been executed.
     *
     * @var array<int, {connectionName: string, time: float, sql: string}>
     */
    protected $queries = [];

    /**
     * Registers the exception renderer listener so that it can listen for events.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher
     * @return void
     */
    public function register(Dispatcher $events)
    {
        $events->listen(QueryExecuted::class, [$this, 'onQueryExecuted']);
    }

    /**
     * Returns the queries that have been executed.
     *
     * @return array<int, {sql: string, time: float}
     */
    public function queries()
    {
        return $this->queries;
    }

    /**
     * Listens for the query executed event.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return void
     */
    public function onQueryExecuted(QueryExecuted $event)
    {
        $this->queries[] = [
            'connectionName' => $event->connectionName,
            'time' => $event->time,
            'sql' => $event->sql,
        ];

        if (count($this->queries) >= 101) {
            array_shift($this->queries);
        }
    }
}
