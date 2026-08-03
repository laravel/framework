<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;
use InvalidArgumentException;

/**
 * @method $this algorithm(string $algorithm) Specify an algorithm for the index (MySQL/PostgreSQL)
 * @method $this deferrable(bool $value = true) Specify that the unique index is deferrable (PostgreSQL)
 * @method $this initiallyImmediate(bool $value = true) Specify the default time to check the unique index constraint (PostgreSQL)
 * @method $this language(string $language) Specify a language for the full text index (PostgreSQL)
 * @method $this lock(('none'|'shared'|'default'|'exclusive') $value) Specify the DDL lock mode for the index operation (MySQL)
 * @method $this nullsNotDistinct(bool $value = true) Specify that the null values should not be treated as distinct (PostgreSQL)
 * @method $this online(bool $value = true) Specify that index creation should not lock the table (PostgreSQL/SqlServer)
 */
class IndexDefinition extends Fluent
{
    /**
     * The valid operators for partial index where clauses.
     *
     * @var list<string>
     */
    protected $operators = ['=', '<', '>', '<=', '>=', '<>', '!=', '<=>'];

    /**
     * Specify a partial index predicate.
     *
     * Semantics match the query builder where possible:
     *
     *     $table->unique('email')->where('deleted_at');          // IS NULL
     *     $table->unique('email')->where('deleted_at', null);    // IS NULL
     *     $table->index('status')->where('active', true);
     *     $table->index('status')->where('active', '=', 1);
     *
     * Use whereRaw() for raw SQL predicates. Partial indexes are supported by
     * PostgreSQL, SQLite, and SQL Server. On PostgreSQL, dropUnique() detects
     * whether the unique is a constraint or a partial index.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function where($column, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            return $this->whereNull($column);
        }

        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        } elseif ($this->invalidOperator($operator)) {
            throw new InvalidArgumentException('Illegal operator for partial index where clause.');
        }

        if (is_null($value)) {
            return in_array($operator, ['=', '<=>'], true)
                ? $this->whereNull($column)
                : $this->whereNotNull($column);
        }

        $this->attributes['where'] = [
            'type' => 'Basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Specify a raw partial index predicate.
     *
     *     $table->unique('email')->whereRaw('deleted_at is null');
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $expression
     * @return $this
     */
    public function whereRaw($expression)
    {
        $this->attributes['where'] = [
            'type' => 'Raw',
            'sql' => $expression,
        ];

        return $this;
    }

    /**
     * Specify a partial index "where null" predicate.
     *
     * Useful for soft-delete-aware unique indexes:
     *
     *     $table->unique(['user_id', 'slug'])->whereNull('deleted_at');
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNull($column)
    {
        $this->attributes['where'] = [
            'type' => 'Null',
            'column' => $column,
        ];

        return $this;
    }

    /**
     * Specify a partial index "where not null" predicate.
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->attributes['where'] = [
            'type' => 'NotNull',
            'column' => $column,
        ];

        return $this;
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param  mixed  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return ! is_string($operator) || ! in_array(strtolower($operator), $this->operators, true);
    }
}
