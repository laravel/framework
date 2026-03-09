<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;

class PendingInsertUsing
{
    /**
     * The source query.
     *
     * @var \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>|null
     */
    protected $query;

    /**
     * The ordered list of column entries.
     *
     * @var array
     */
    protected $entries = [];

    /**
     * Create a new pending insert-using instance.
     *
     * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar  The grammar instance.
     */
    public function __construct(protected Grammars\Grammar $grammar)
    {
    }

    /**
     * Set the source query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $query
     * @return $this
     */
    public function from($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Map a target column to a source column from the subquery.
     *
     * @param  string  $target
     * @param  string  $source
     * @return $this
     */
    public function column(string $target, string $source)
    {
        $this->entries[] = ['type' => 'column', 'target' => $target, 'source' => $source];

        return $this;
    }

    /**
     * Map target column(s) to literal value(s).
     *
     * @param  string|array  $target
     * @param  mixed  $value
     * @return $this
     */
    public function value(string|array $target, mixed $value = null)
    {
        if (is_array($target)) {
            foreach ($target as $column => $val) {
                $this->entries[] = ['type' => 'value', 'target' => $column, 'value' => $val];
            }

            return $this;
        }

        $this->entries[] = ['type' => 'value', 'target' => $target, 'value' => $value];

        return $this;
    }

    /**
     * Get the source query.
     *
     * @return \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>|\Illuminate\Database\Eloquent\Relations\Relation<*, *, *>|null
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the target column names in definition order.
     *
     * @return array
     */
    public function getColumns()
    {
        return array_map(fn ($entry) => $entry['target'], $this->entries);
    }

    /**
     * Apply the column and value definitions to a query's select list.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>  $query
     * @return void
     */
    public function applyToQuery($query)
    {
        $selects = [];
        $bindings = [];

        foreach ($this->entries as $entry) {
            if ($entry['type'] === 'column') {
                $selects[] = $entry['source'];
            } else {
                $value = $entry['value'];

                if ($value instanceof ExpressionContract) {
                    $selects[] = new Expression($this->grammar->getValue($value));
                } else {
                    $selects[] = new Expression('?');
                    $bindings[] = $value;
                }
            }
        }

        $query->select($selects);

        if ($bindings) {
            $query->addBinding($bindings, 'select');
        }
    }
}
