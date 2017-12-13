<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;
use PDOStatement;

class StatementPrepared
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The PDO statement.
     *
     * @var \PDOStatement
     */
    public $statement;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \PDOStatement  $statement
     * @return void
     */
    public function __construct(Connection $connection, PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->connection = $connection;
    }
}
