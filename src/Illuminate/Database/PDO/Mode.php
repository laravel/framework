<?php

namespace Illuminate\Database\PDO;

use Illuminate\Database\ConnectionInterface;
use PDO;
use RuntimeException;

class Mode
{
    /**
     * The name of the mode.
     *
     * @var string|null
     */
    protected string|null $name = null;

    /**
     * The driver of the PDO connection.
     *
     * @var string|null
     */
    protected string|null $driver = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected array $arguments = [],
        protected array $prepareArguments = [],
    ) {
        //
    }

    /**
     * The arguments for the `fetch()` or `fetchAll()` call.
     *
     * @return array
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * The arguments for the `prepare()` call.
     *
     * @return array
     */
    public function prepareArguments(): array
    {
        return $this->prepareArguments;
    }

    /**
     * Retrieve a single column.
     *
     * @param int  $position
     * @return self
     */
    public function column(int $position = 0): self
    {
        $this->name = __FUNCTION__;
        $this->arguments = [PDO::FETCH_COLUMN, $position];

        return $this;
    }

    /**
     * Use the first SELECT column as the array key, and the second SELECT column as the value.
     *
     * @return self
     */
    public function pair(): self
    {
        $this->name = __FUNCTION__;
        $this->arguments = [PDO::FETCH_KEY_PAIR];

        return $this;
    }

    /**
     * Use the first SELECT column as the array key. This column is consumed in the process.
     *
     * @return self
     */
    public function keyed(): self
    {
        $this->name = __FUNCTION__;
        $this->arguments = [PDO::FETCH_UNIQUE];

        return $this;
    }

    /**
     * Use a different buffer mode.
     *
     * See: https://www.php.net/manual/en/mysqlinfo.concepts.buffering.php
     *
     * @param  bool  $buffered
     * @return $this
     */
    public function buffered(bool $buffered = true): self
    {
        if ($this->driver() !== 'mysql') {
            throw new RuntimeException('Buffered mode requires a MySQL connection.');
        }

        $this->name = __FUNCTION__;
        $this->connection->getPdo()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);

        return $this;
    }

    /**
     * Use a scrollable cursor, which allows every nth row to be fetched.
     *
     * @param int $nth
     * @return $this
     */
    public function scrollableCursor(int $nth = 1): self
    {
        if (! in_array($this->driver(), ['pgsql', 'sqlsrv'])) {
            throw new RuntimeException('Scrollable cursor requires a PostgreSQL or SQL Server connection.');
        }

        $this->name = __FUNCTION__;
        // Set cursor orientation as "relative".
        $this->arguments = [$this->connection->getDefaultFetchMode(), PDO::FETCH_ORI_REL, $nth];
        $this->prepareArguments = [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL];

        return $this;
    }

    /**
     * Get the driver of this PDO connection.
     *
     * @return string
     */
    protected function driver(): string
    {
        if (! is_null($driver = $this->driver)) {
            return $driver;
        }

        return $this->driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
