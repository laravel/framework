<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Database\Schema\Builder create(string $table, \Closure $callback)
 * @method static \Illuminate\Database\Schema\Builder drop(string $table)
 * @method static \Illuminate\Database\Schema\Builder dropIfExists(string $table)
 * @method static \Illuminate\Database\Schema\Builder table(string $table, \Closure $callback)
 * @method static void dropAllTables() Drop all tables from the database.
 * @method static void refreshDatabaseFile() Delete the database file & re-create it.
 * @method static void defaultStringLength(int $length) Set the default string length for migrations.
 * @method static bool hasTable(string $table) Determine if the given table exists.
 * @method static bool hasColumn(string $table, string $column) Determine if the given table has a given column.
 * @method static bool hasColumns(string $table, array $columns) Determine if the given table has given columns.
 * @method static string getColumnType(string $table, string $column) Get the data type for the given column name.
 * @method static array getColumnListing(string $table) Get the column listing for a given table.
 * @method static void rename(string $from, string $to) Rename a table on the schema.
 * @method static bool enableForeignKeyConstraints() Enable foreign key constraints.
 * @method static bool disableForeignKeyConstraints() Disable foreign key constraints.
 * @method static \Illuminate\Database\Connection getConnection() Get the database connection instance.
 * @method static $this setConnection(\Illuminate\Database\Connection $connection) Set the database connection instance.
 * @method static void blueprintResolver(\Closure $resolver) Set the Schema Blueprint resolver callback.
 *
 * @see \Illuminate\Database\Schema\Builder
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        return static::$app['db']->connection($name)->getSchemaBuilder();
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['db']->connection()->getSchemaBuilder();
    }
}
