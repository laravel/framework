<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Database\Connection connection(string $name) Get a database connection instance.
 * @method static void purge(string $name) Disconnect from the given database and remove from local cache.
 * @method static void disconnect(string $name) Disconnect from the given database.
 * @method static \Illuminate\Database\Connection reconnect(string $name) Reconnect to the given database.
 * @method static string getDefaultConnection() Get the default connection name.
 * @method static void setDefaultConnection(string $name) Set the default connection name.
 * @method static array supportedDrivers() Get all of the support drivers.
 * @method static array availableDrivers() Get all of the drivers that are actually available.
 * @method static void extend(string $name, callable $resolver) Register an extension connection resolver.
 * @method static array getConnections() Return all of the created connections.
 * @method static \Illuminate\Database\Schema\SQLiteBuilder getSchemaBuilder() Get a schema builder instance for the connection.
 * @method static void useDefaultQueryGrammar() Set the query grammar to the default implementation.
 * @method static void useDefaultSchemaGrammar() Set the schema grammar to the default implementation.
 * @method static void useDefaultPostProcessor() Set the query post processor to the default implementation.
 * @method static \Illuminate\Database\Query\Builder table(string $table) Begin a fluent query against a database table.
 * @method static \Illuminate\Database\Query\Builder query() Get a new query builder instance.
 * @method static mixed selectOne(string $query, array $bindings, bool $useReadPdo) Run a select statement and return a single result.
 * @method static array selectFromWriteConnection(string $query, array $bindings) Run a select statement against the database.
 * @method static array select(string $query, array $bindings, bool $useReadPdo) Run a select statement against the database.
 * @method static \Generator cursor(string $query, array $bindings, bool $useReadPdo) Run a select statement against the database and returns a generator.
 * @method static bool insert(string $query, array $bindings) Run an insert statement against the database.
 * @method static int update(string $query, array $bindings) Run an update statement against the database.
 * @method static int delete(string $query, array $bindings) Run a delete statement against the database.
 * @method static bool statement(string $query, array $bindings) Execute an SQL statement and return the boolean result.
 * @method static int affectingStatement(string $query, array $bindings) Run an SQL statement and get the number of rows affected.
 * @method static bool unprepared(string $query) Run a raw, unprepared query against the PDO connection.
 * @method static array pretend(\Closure $callback) Execute the given callback in "dry run" mode.
 * @method static void bindValues(\PDOStatement $statement, array $bindings) Bind values to their parameters in the given statement.
 * @method static array prepareBindings(array $bindings) Prepare the query bindings for execution.
 * @method static void logQuery(string $query, array $bindings, float | null $time) Log a query in the connection's query log.
 * @method static void listen(\Closure $callback) Register a database query listener with the connection.
 * @method static \Illuminate\Database\Query\Expression raw(mixed $value) Get a new raw query expression.
 * @method static void recordsHaveBeenModified(bool $value) Indicate if any records have been modified.
 * @method static bool isDoctrineAvailable() Is Doctrine available?
 * @method static \Doctrine\DBAL\Schema\Column getDoctrineColumn(string $table, string $column) Get a Doctrine Schema Column instance.
 * @method static \Doctrine\DBAL\Schema\AbstractSchemaManager getDoctrineSchemaManager() Get the Doctrine DBAL schema manager for the connection.
 * @method static \Doctrine\DBAL\Connection getDoctrineConnection() Get the Doctrine DBAL database connection instance.
 * @method static \PDO getPdo() Get the current PDO connection.
 * @method static \PDO getReadPdo() Get the current PDO connection used for reading.
 * @method static $this setPdo(\PDO | \Closure | null $pdo) Set the PDO connection.
 * @method static $this setReadPdo(\PDO | \Closure | null $pdo) Set the PDO connection used for reading.
 * @method static $this setReconnector(callable $reconnector) Set the reconnect instance on the connection.
 * @method static string|null getName() Get the database connection name.
 * @method static mixed getConfig(string | null $option) Get an option from the configuration options.
 * @method static string getDriverName() Get the PDO driver name.
 * @method static \Illuminate\Database\Query\Grammars\Grammar getQueryGrammar() Get the query grammar used by the connection.
 * @method static void setQueryGrammar(\Illuminate\Database\Query\Grammars\Grammar $grammar) Set the query grammar used by the connection.
 * @method static \Illuminate\Database\Schema\Grammars\Grammar getSchemaGrammar() Get the schema grammar used by the connection.
 * @method static void setSchemaGrammar(\Illuminate\Database\Schema\Grammars\Grammar $grammar) Set the schema grammar used by the connection.
 * @method static \Illuminate\Database\Query\Processors\Processor getPostProcessor() Get the query post processor used by the connection.
 * @method static void setPostProcessor(\Illuminate\Database\Query\Processors\Processor $processor) Set the query post processor used by the connection.
 * @method static \Illuminate\Contracts\Events\Dispatcher getEventDispatcher() Get the event dispatcher used by the connection.
 * @method static void setEventDispatcher(\Illuminate\Contracts\Events\Dispatcher $events) Set the event dispatcher instance on the connection.
 * @method static bool pretending() Determine if the connection in a "dry run".
 * @method static array getQueryLog() Get the connection query log.
 * @method static void flushQueryLog() Clear the query log.
 * @method static void enableQueryLog() Enable the query log on the connection.
 * @method static void disableQueryLog() Disable the query log on the connection.
 * @method static bool logging() Determine whether we're logging queries.
 * @method static string getDatabaseName() Get the name of the connected database.
 * @method static string setDatabaseName(string $database) Set the name of the connected database.
 * @method static string getTablePrefix() Get the table prefix for the connection.
 * @method static void setTablePrefix(string $prefix) Set the table prefix in use by the connection.
 * @method static \Illuminate\Database\Grammar withTablePrefix(\Illuminate\Database\Grammar $grammar) Set the table prefix and return the grammar.
 * @method static void resolverFor(string $driver, \Closure $callback) Register a connection resolver.
 * @method static mixed getResolver(string $driver) Get the connection resolver for the given driver.
 * @method static mixed transaction(\Closure $callback, int $attempts) Execute a Closure within a transaction.
 * @method static void beginTransaction() Start a new database transaction.
 * @method static void commit() Commit the active database transaction.
 * @method static void rollBack(int | null $toLevel) Rollback the active database transaction.
 * @method static int transactionLevel() Get the number of active transactions.
 *
 * @see \Illuminate\Database\DatabaseManager
 * @see \Illuminate\Database\Connection
 */
class DB extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'db';
    }
}
