<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class QueryExecuted
{
    /**
     * The database connection name.
     */
    public string $connectionName;

    /**
     * Create a new event instance.
     *
     * @param  null|'read'|'write'  $readWriteType
     */
    public function __construct(
        public string $sql,
        public array $bindings,
        public ?float $time,
        public Connection $connection,
        public ?string $readWriteType = null,
    ) {
        $this->connectionName = $connection->getName();
    }

    /**
     * Get the raw SQL representation of the query with embedded bindings.
     *
     * @return string
     */
    public function toRawSql()
    {
        return $this->connection
            ->query()
            ->getGrammar()
            ->substituteBindingsIntoRawSql($this->sql, $this->connection->prepareBindings($this->bindings));
    }
}
