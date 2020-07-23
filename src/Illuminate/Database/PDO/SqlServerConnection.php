<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\PDO\SQLSrv\Statement;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;

class SqlServerConnection implements ServerInfoAwareConnection
{
    /**
     * @var \Illuminate\Database\PDO\Connection
     */
    private $connection;

    /**
     * Create a new SQL Server connection instance.
     *
     * @param  \Illuminate\Database\PDO\Connection  $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function prepare(string $sql): StatementInterface
    {
        return new Statement(
            $this->connection->prepare($sql)
        );
    }

    public function query(string $sql): Result
    {
        return $this->connection->query($sql);
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        $val = $this->connection->quote($value, $type);

        // Fix for a driver version terminating all values with null byte
        if (\is_string($val) && \strpos($val, "\0") !== false) {
            $val = \substr($val, 0, -1);
        }

        return $val;
    }

    public function exec(string $statement): int
    {
        return $this->connection->exec($statement);
    }

    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return $this->connection->lastInsertId($name);
        }

        return $this->prepare('SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?')
            ->execute([$name])
            ->fetchOne();
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    public function getServerVersion()
    {
        return $this->connection->getServerVersion();
    }

    public function getWrappedConnection(): PDO
    {
        return $this->connection->getWrappedConnection();
    }
}
