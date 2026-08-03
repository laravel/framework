<?php

namespace Illuminate\Database\Schema;

use Illuminate\Support\Fluent;

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
     * Specify a partial index predicate.
     *
     * Supported by PostgreSQL, SQLite, and SQL Server. MySQL and MariaDB do not
     * support partial indexes and will throw a RuntimeException when compiling.
     *
     * When a single argument is provided, it is treated as a raw SQL fragment:
     *
     *     $table->unique('email')->where('deleted_at is null');
     *
     * Column / operator / value forms are also supported:
     *
     *     $table->index('status')->where('active', true);
     *     $table->index('status')->where('active', '=', 1);
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $this->attributes['where'] = [
                'type' => 'Raw',
                'sql' => $column,
            ];

            return $this;
        }

        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
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
}
