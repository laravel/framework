<?php

namespace Illuminate\Database\Schema;

use Closure;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class Builder
{
    /**
     * The default string length for migrations.
     *
     * @var int
     */
    public static $defaultStringLength = 255;
    /**
     * The default relationship morph key type.
     *
     * @var string
     */
    public static $defaultMorphKeyType = 'int';
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected $connection;
    /**
     * The schema grammar instance.
     *
     * @var Grammar
     */
    protected $grammar;
    /**
     * The Blueprint resolver callback.
     *
     * @var Closure
     */
    protected $resolver;

    /**
     * Create a new database Schema manager.
     *
     * @param Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Set the default string length for migrations.
     *
     * @param int $length
     * @return void
     */
    public static function defaultStringLength($length)
    {
        static::$defaultStringLength = $length;
    }

    /**
     * Set the default morph key type for migrations to UUIDs.
     *
     * @return void
     */
    public static function morphUsingUuids()
    {
        return static::defaultMorphKeyType('uuid');
    }

    /**
     * Set the default morph key type for migrations.
     *
     * @param string $type
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public static function defaultMorphKeyType(string $type)
    {
        if (!in_array($type, ['int', 'uuid'])) {
            throw new InvalidArgumentException("Morph key type must be 'int' or 'uuid'.");
        }

        static::$defaultMorphKeyType = $type;
    }

    /**
     * Create a database in the schema.
     *
     * @param string $name
     * @return bool
     *
     * @throws LogicException
     */
    public function createDatabase($name)
    {
        throw new LogicException('This database driver does not support creating databases.');
    }

    /**
     * Drop a database from the schema if the database exists.
     *
     * @param string $name
     * @return bool
     *
     * @throws LogicException
     */
    public function dropDatabaseIfExists($name)
    {
        throw new LogicException('This database driver does not support dropping databases.');
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     * @return bool
     */
    public function hasTable($table)
    {
        $table = $this->connection->getTablePrefix() . $table;

        return count(
                $this->connection->selectFromWriteConnection(
                    $this->grammar->compileTableExists(), [$table]
                )
            ) > 0;
    }

    /**
     * Determine if the given table has a given column.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return in_array(
            strtolower($column),
            array_map('strtolower', $this->getColumnListing($table))
        );
    }

    /**
     * Get the column listing for a given table.
     *
     * @param string $table
     * @return array
     */
    public function getColumnListing($table)
    {
        $results = $this->connection->selectFromWriteConnection(
            $this->grammar->compileColumnListing(
                $this->connection->getTablePrefix() . $table
            )
        );

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param string $table
     * @param array $columns
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        $tableColumns = array_map('strtolower', $this->getColumnListing($table));

        foreach ($columns as $column) {
            if (!in_array(strtolower($column), $tableColumns)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the data type for the given column name.
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    public function getColumnType($table, $column)
    {
        $table = $this->connection->getTablePrefix() . $table;

        return $this->connection->getDoctrineColumn($table, $column)->getType()->getName();
    }

    /*
     * Determine if the given table has a given index in given column(s).
     * @return bool
     * */
    public function hasIndex($table, string|array $column, $indexName = null): bool
    {
        if (is_null($indexName) && !is_array($column)) {
            $indexName = $table . '_' . $column . '_index';
        }
        if (!is_array($column)) {
            $column = [$column];
        }
        $table = $this->connection->getTablePrefix() . $table;

        $tableIndexes = $this->connection->getDoctrineSchemaManager()->listTableIndexes($table);

        foreach ($tableIndexes as $tableIndex) {
            if ($tableIndex->getName() === $indexName && !count(array_diff($column, $tableIndex->getColumns()))) {
                return true;
            }
        }
        return false;
    }


    /**
     * Create a new table on the schema.
     *
     * @param string $table
     * @param Closure $callback
     * @return void
     */
    public function create($table, Closure $callback)
    {
        $this->build(
            tap($this->createBlueprint($table), function ($blueprint) use ($callback) {
                $blueprint->create();

                $callback($blueprint);
            })
        );
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * @param Blueprint $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string $table
     * @param Closure|null $callback
     * @return Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        return new Blueprint($table, $callback, $prefix);
    }

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     * @return void
     */
    public function drop($table)
    {
        $this->build(
            tap($this->createBlueprint($table), function ($blueprint) {
                $blueprint->drop();
            })
        );
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param string $table
     * @return void
     */
    public function dropIfExists($table)
    {
        $this->build(
            tap($this->createBlueprint($table), function ($blueprint) {
                $blueprint->dropIfExists();
            })
        );
    }

    /**
     * Drop columns from a table schema.
     *
     * @param string $table
     * @param string|array $columns
     * @return void
     */
    public function dropColumns($table, $columns)
    {
        $this->table($table, function (Blueprint $blueprint) use ($columns) {
            $blueprint->dropColumn($columns);
        });
    }

    /**
     * Modify a table on the schema.
     *
     * @param string $table
     * @param Closure $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        $this->build($this->createBlueprint($table, $callback));
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function dropAllTables()
    {
        throw new LogicException('This database driver does not support dropping all tables.');
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function dropAllViews()
    {
        throw new LogicException('This database driver does not support dropping all views.');
    }

    /**
     * Drop all types from the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function dropAllTypes()
    {
        throw new LogicException('This database driver does not support dropping all types.');
    }

    /**
     * Get all of the table names for the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function getAllTables()
    {
        throw new LogicException('This database driver does not support getting all tables.');
    }

    /**
     * Rename a table on the schema.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function rename($from, $to)
    {
        $this->build(
            tap($this->createBlueprint($from), function ($blueprint) use ($to) {
                $blueprint->rename($to);
            })
        );
    }

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enableForeignKeyConstraints()
    {
        return $this->connection->statement(
            $this->grammar->compileEnableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        return $this->connection->statement(
            $this->grammar->compileDisableForeignKeyConstraints()
        );
    }

    /**
     * Register a custom Doctrine mapping type.
     *
     * @param string $class
     * @param string $name
     * @param string $type
     * @return void
     *
     * @throws DBALException
     * @throws RuntimeException
     */
    public function registerCustomDoctrineType($class, $name, $type)
    {
        if (!$this->connection->isDoctrineAvailable()) {
            throw new RuntimeException(
                'Registering a custom Doctrine type requires Doctrine DBAL (doctrine/dbal).'
            );
        }

        if (!Type::hasType($name)) {
            Type::addType($name, $class);

            $this->connection
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping($type, $name);
        }
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the database connection instance.
     *
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param Closure $resolver
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }
}
