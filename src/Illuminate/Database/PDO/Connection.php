<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\Connection as ConnectionContract;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\PDO\Result;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;

class Connection implements ConnectionContract
{
    /**
     * The underlying PDO connection.
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Create a new PDO connection instance.
     *
     * @param  \PDO  $connection
     * @return void
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute an SQL statement.
     *
     * @param  string  $statement
     * @return int
     */
    public function exec(string $statement): int
    {
        try {
            $result = $this->connection->exec($statement);

            \assert($result !== false);

            return $result;
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Prepare a new SQL statement.
     *
     * @param  string  $sql
     * @return \Doctrine\DBAL\Driver\Statement
     *
     * @throws \Doctrine\DBAL\Driver\PDO\Exception
     */
    public function prepare(string $sql): StatementInterface
    {
        try {
            return $this->createStatement(
                $this->connection->prepare($sql)
            );
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Execute a new query against the connection.
     *
     * @param  string  $sql
     * @return \Doctrine\DBAL\Driver\Result
     */
    public function query(string $sql): ResultInterface
    {
        try {
            $stmt = $this->connection->query($sql);

            \assert($stmt instanceof PDOStatement);

            return new Result($stmt);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Get the last insert ID.
     *
     * @param  string|null  $name
     * @return string|int
     *
     * @throws \Doctrine\DBAL\Driver\PDO\Exception
     */
    public function lastInsertId($name = null): string|int
    {
        try {
            if ($name === null) {
                return $this->connection->lastInsertId();
            }

            return $this->connection->lastInsertId($name);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Create a new statement instance.
     *
     * @param  \PDOStatement  $stmt
     * @return \Doctrine\DBAL\Driver\PDO\Statement
     */
    protected function createStatement(PDOStatement $stmt): Statement
    {
        return new Statement($stmt);
    }

    /**
     * Begin a new database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback a database transaction.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Wrap quotes around the given input.
     *
     * @param  string  $input
     * @param  string  $type
     * @return string
     */
    public function quote($input, $type = ParameterType::STRING): string
    {
        return $this->connection->quote($input, $type);
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get the native PDO connection.
     *
     * @return \PDO
     */
    public function getNativeConnection(): PDO
    {
        return $this->connection;
    }
}
