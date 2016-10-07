<?php

namespace Illuminate\Validation\Rules;

use Closure;

class Unique
{
    /**
     * The table to run the query against.
     *
     * @var string
     */
    protected $table;

    /**
     * The column to check for uniqueness on.
     *
     * @var string
     */
    protected $column;

    /**
     * The ID that should be ignored.
     *
     * @var mixed
     */
    protected $ignore;

    /**
     * The name of the ID column.
     *
     * @var string
     */
    protected $idColumn = 'id';

    /**
     * There extra where clauses for the query.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The custom query callback.
     *
     * @var \Closure|null
     */
    protected $using;

    /**
     * Create a new unique rule instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
     */
    public function __construct($table, $column = 'NULL')
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Set a "where" constraint on the query.
     *
     * @param  string  $column
     * @param  string  $value
     * @return $this
     */
    public function where($column, $value = null)
    {
        if ($column instanceof Closure) {
            return $this->using($column);
        }

        $this->wheres[] = compact('column', 'value');

        return $this;
    }

    /**
     * Set a "where not" constraint on the query.
     *
     * @param  string  $column
     * @param  string  $value
     * @return $this
     */
    public function whereNot($column, $value)
    {
        return $this->where($column, '!'.$value);
    }

    /**
     * Set a "where null" constraint on the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNull($column)
    {
        return $this->where($column, 'NULL');
    }

    /**
     * Set a "where not null" constraint on the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        return $this->where($column, 'NOT_NULL');
    }

    /**
     * Ignore the given ID during the unique check.
     *
     * @param  mixed  $id
     * @param  string  $idColumn
     * @return $this
     */
    public function ignore($id, $idColumn = 'id')
    {
        $this->ignore = $id;
        $this->idColumn = $idColumn;

        return $this;
    }

    /**
     * Register a custom query callback.
     *
     * @param  \Closure  $callback
     */
    public function using(Closure $callback)
    {
        $this->using = $callback;

        return $this;
    }

    /**
     * Format the where clauses.
     *
     * @return string
     */
    protected function formatWheres()
    {
        return collect($this->wheres)->map(function ($where) {
            return $where['column'].','.$where['value'];
        })->implode(',');
    }

    /**
     * Get the custom query callbacks for the rule.
     *
     * @return array
     */
    public function queryCallbacks()
    {
        return $this->using ? [$this->using] : [];
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('unique:%s,%s,%s,%s,%s',
            $this->table,
            $this->column,
            $this->ignore ?: 'NULL',
            $this->idColumn,
            $this->formatWheres()
        ), ',');
    }
}
