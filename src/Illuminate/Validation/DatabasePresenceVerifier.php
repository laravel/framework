<?php

namespace Illuminate\Validation;

use Closure;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

class DatabasePresenceVerifier implements DatabasePresenceVerifierInterface
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $db;

    /**
     * The database connection to use.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Create a new database presence verifier.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $db
     */
    public function __construct(ConnectionResolverInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Count the number of objects in a collection having the given value.
     *
     * @param  string  $collection
     * @param  string  $column
     * @param  string  $value
     * @param  int|string|null  $excludeId
     * @param  string|null  $idColumn
     * @param  array  $extra
     * @return int
     */
    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        $query = $this->table($collection);

        $query->where($this->prepareColumn($query, $column), '=', $this->prepareValue($value));

        if (! is_null($excludeId) && $excludeId !== 'NULL') {
            $query->where($idColumn ?: 'id', '<>', $excludeId);
        }

        return $this->addConditions($query, $extra)->count();
    }

    /**
     * Count the number of objects in a collection with the given values.
     *
     * @param  string  $collection
     * @param  string  $column
     * @param  array  $values
     * @param  array  $extra
     * @return int
     */
    public function getMultiCount($collection, $column, array $values, array $extra = [])
    {
        $query = $this->table($collection);

        $column = $this->prepareColumn($query, $column);

        $query->whereIn($column, array_map($this->prepareValue(...), $values));

        return $this->addConditions($query, $extra)->distinct()->count($column);
    }

    /**
     * Prepare a column for comparison with a string value.
     *
     * SQLite extracts JSON values with their JSON type, so a JSON number never
     * equals a string. Other databases already extract JSON values as text.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $column
     * @return \Illuminate\Contracts\Database\Query\Expression|string
     */
    protected function prepareColumn($query, $column)
    {
        if (! str_contains($column, '->') || ! $query->getGrammar() instanceof SQLiteGrammar) {
            return $column;
        }

        return new Expression('cast('.$query->getGrammar()->wrap($column).' as text)');
    }

    /**
     * Prepare a value for comparison with a database column.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function prepareValue($value)
    {
        return match (true) {
            is_bool($value) => (string) (int) $value,
            is_int($value) => (string) $value,
            default => $value,
        };
    }

    /**
     * Add the given conditions to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $conditions
     * @return \Illuminate\Database\Query\Builder
     */
    protected function addConditions($query, $conditions)
    {
        foreach ($conditions as $key => $value) {
            if ($value instanceof Closure) {
                $query->where(function ($query) use ($value) {
                    $value($query);
                });
            } else {
                $this->addWhere($query, $key, $value);
            }
        }

        return $query;
    }

    /**
     * Add a "where" clause to the given query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $key
     * @param  string  $extraValue
     * @return void
     */
    protected function addWhere($query, $key, $extraValue)
    {
        if ($extraValue === 'NULL') {
            $query->whereNull($key);
        } elseif ($extraValue === 'NOT_NULL') {
            $query->whereNotNull($key);
        } elseif (str_starts_with($extraValue, '!')) {
            $query->where($key, '!=', mb_substr($extraValue, 1));
        } else {
            $query->where($key, $extraValue);
        }
    }

    /**
     * Get a query builder for the given table.
     *
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table($table)
    {
        return $this->db->connection($this->connection)->table($table)->useWritePdo();
    }

    /**
     * Set the connection to be used.
     *
     * @param  string|null  $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }
}
