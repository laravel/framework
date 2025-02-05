<?php

namespace Illuminate\Database\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Arr;

trait BuildsDateWhereClauses
{
    /**
     * Add a where clause to determine if a "date" column is in the past to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $now
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function wherePast($columns, $value = null, $boolean = 'and', $not = false)
    {
        $type = 'Basic';
        $operator = $not ? '>=' : '<';
        $value = $value ?? Carbon::now();

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean', 'operator', 'value');

            $this->addBinding($value);
        }

        return $this;
    }

    /**
     * Add an "or where" clause to determine if a "date" column is in the past to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @return $this
     */
    public function orWherePast($columns, $value = null)
    {
        return $this->wherePast($columns, $value, 'or');
    }

    /**
     * Add a where clause to determine if a "date" column is not in the past to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @return $this
     */
    public function whereNotPast($columns, $value = null)
    {
        return $this->wherePast($columns, $value, 'and', true);
    }

    /**
     * Add an "or where" clause to determine if a "date" column is in the past to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @return $this
     */
    public function orWhereNotPast($columns, $value = null)
    {
        return $this->wherePast($columns, $value, 'or', true);
    }

    /**
     * Add a "where date" clause to determine if a "date" column is today to the query.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereToday($columns, $boolean = 'and', $not = false)
    {
        $operator = $not ? '!=' : '=';

        $value = Carbon::today()->format('Y-m-d');

        foreach (Arr::wrap($columns) as $column) {
            $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
        }

        return $this;
    }

    /**
     * Add a "where date" clause to determine if a "date" column is before today.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereBeforeToday($columns, $boolean = 'and')
    {
        return $this->whereTodayBeforeOrAfter($columns, '<', $boolean);
    }

    /**
     * Add a "where date" clause to determine if a "date" column is today or before to the query.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereTodayOrBefore($columns, $boolean = 'and')
    {
        return $this->whereTodayBeforeOrAfter($columns, '<=', $boolean);
    }

    /**
     * Add a "where date" clause to determine if a "date" column is after today.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereAfterToday($columns, $boolean = 'and')
    {
        return $this->whereTodayBeforeOrAfter($columns, '>', $boolean);
    }

    /**
     * Add a "where date" clause to determine if a "date" column is today or after to the query.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function whereTodayOrAfter($columns, $boolean = 'and')
    {
        return $this->whereTodayBeforeOrAfter($columns, '>=', $boolean);
    }

    /**
     * Add an "or where date" clause to determine if a "date" column is today to the query.
     *
     * @param  array|string  $columns
     * @return $this
     */
    public function orWhereToday($columns)
    {
        return $this->whereToday($columns, 'or');
    }

    /**
     * Add an "or where date" clause to determine if a "date" column is before today.
     *
     * @param  array|string  $columns
     * @return $this
     */
    public function orWhereBeforeToday($columns)
    {
        return $this->whereTodayBeforeOrAfter($columns, '<', 'or');
    }

    /**
     * Add an "or where date" clause to determine if a "date" column is today or before to the query.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function orWhereTodayOrBefore($columns)
    {
        return $this->whereTodayBeforeOrAfter($columns, '<=', 'or');
    }

    /**
     * Add an "or where date" clause to determine if a "date" column is after today.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function orWhereAfterToday($columns)
    {
        return $this->whereTodayBeforeOrAfter($columns, '>', 'or');
    }

    /**
     * Add an "or where date" clause to determine if a "date" column is today or after to the query.
     *
     * @param  array|string  $columns
     * @param  string  $boolean
     * @return $this
     */
    public function orWhereTodayOrAfter($columns)
    {
        return $this->whereTodayBeforeOrAfter($columns, '>=', 'or');
    }

    /**
     * Add a "where date" clause to determine if a "date" column is today or after to the query.
     *
     * @param  array|string  $columns
     * @param  string  $operator
     * @param  string  $boolean
     * @return $this
     */
    protected function whereTodayBeforeOrAfter($columns, $operator, $boolean)
    {
        $value = Carbon::today()->format('Y-m-d');

        foreach (Arr::wrap($columns) as $column) {
            $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
        }

        return $this;
    }

    /**
     * Add a "where date" clause to determine if a "date" column is not today to the query.
     *
     * @param  array|string  $columns
     * @return $this
     */
    public function whereNotToday($columns)
    {
        return $this->whereToday($columns, 'and', true);
    }

    /**
     * Add an "or where date" clause to determine if a "date" column is not today to the query.
     *
     * @param  array|string  $columns
     * @return $this
     */
    public function orWhereNotToday($columns)
    {
        return $this->whereToday($columns, 'or', true);
    }

    /**
     * Add a where clause to determine if a "date" column is in the future to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereFuture($columns, $value = null, $boolean = 'and', $not = false)
    {
        $type = 'Basic';
        $operator = $not ? '<=' : '>';
        $value = $value ?? Carbon::now();

        foreach (Arr::wrap($columns) as $column) {
            $this->wheres[] = compact('type', 'column', 'boolean', 'operator', 'value');

            $this->addBinding($value);
        }

        return $this;
    }

    /**
     * Add an "or where" clause to determine if a "date" column is in the future to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @return $this
     */
    public function orWhereFuture($columns, $value = null)
    {
        return $this->whereFuture($columns, $value, 'or');
    }

    /**
     * Add a where clause to determine if a "date" column is not in the future to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @return $this
     */
    public function whereNotFuture($columns, $value = null)
    {
        return $this->whereFuture($columns, $value, 'and', true);
    }

    /**
     * Add an "or where" clause to determine if a "date" column is in the future to the query.
     *
     * @param  array|string  $columns
     * @param  \DateTimeInterface|string|null  $value
     * @return $this
     */
    public function orWhereNotFuture($columns, $value = null)
    {
        return $this->whereFuture($columns, $value, 'or', true);
    }
}
