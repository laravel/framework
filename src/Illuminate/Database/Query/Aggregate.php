<?php

namespace Illuminate\Database\Query;

use Closure;
use InvalidArgumentException;

/**
 * Fluent definition for a single aggregate used by
 * {@see \Illuminate\Database\Query\Builder::withSequentialPeriodMetrics()}.
 *
 * The `$column` may be:
 *
 *  - a string column name (e.g. `'revenue'` or `'*'`)
 *  - an {@see \Illuminate\Database\Query\Expression} for raw SQL
 *    (e.g. `new Expression('revenue * quantity')`, typically via `DB::raw(...)`)
 *  - a {@see \Closure} that receives a fresh sub-query builder and lets you
 *    compose a correlated sub-query used as the aggregate input.
 *
 * Typical usage:
 *
 *     Aggregate::sum('revenue')->as('total_revenue')->comparison(SequentialPeriodComparison::Percent)
 *     Aggregate::count('*')->as('order_count')->comparisons([SequentialPeriodComparison::Percent, 'difference'])
 *     Aggregate::avg('cost')->withoutComparison()
 *     Aggregate::sum(DB::raw('revenue * quantity'))->as('gross_revenue')
 *     Aggregate::sum(fn ($query) => $query->from('order_items')->selectRaw('sum(price * qty)')->whereColumn('order_items.order_id', 'orders.id'))->as('gross_revenue')
 */
class Aggregate
{
    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     * @param  list<SequentialPeriodComparison|string>  $comparisons
     * @param  int|null  $precision  Number of decimals for the aggregate value and
     *                               its comparison columns. Null leaves aggregate and
     *                               difference unrounded and uses the default 2
     *                               decimals for the percent change.
     * @param  string|null  $thousandsSeparator  When set, the aggregate, previous-period
     *                                           and comparison columns are returned as
     *                                           a locale-formatted string (e.g. "1.234,56")
     *                                           applied in PHP via a post-query callback.
     * @param  string|null  $decimalSeparator  Decimal separator used together with
     *                                         $thousandsSeparator. Either separator
     *                                         enables the formatting; the other one
     *                                         defaults to "" (no grouping) or "." (dot decimal).
     */
    public function __construct(
        public Closure|Expression|string $column,
        public string $function = 'sum',
        public ?string $alias = null,
        public array $comparisons = [SequentialPeriodComparison::Percent],
        public ?int $precision = null,
        public ?string $thousandsSeparator = null,
        public ?string $decimalSeparator = null,
    ) {
        if ($this->precision !== null && $this->precision < 0) {
            throw new InvalidArgumentException('Aggregate precision must be a non-negative integer or null.');
        }
    }

    /**
     * Start a new aggregate against the given column. Function defaults to "sum".
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    public static function column(Closure|Expression|string $column): static
    {
        return new static($column);
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    public static function sum(Closure|Expression|string $column): static
    {
        return new static($column, 'sum');
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    public static function avg(Closure|Expression|string $column): static
    {
        return new static($column, 'avg');
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    public static function min(Closure|Expression|string $column): static
    {
        return new static($column, 'min');
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    public static function max(Closure|Expression|string $column): static
    {
        return new static($column, 'max');
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    public static function count(Closure|Expression|string $column = '*'): static
    {
        return new static($column, 'count');
    }

    /**
     * Override the aggregate function after the fact (e.g. when starting from column()).
     */
    public function using(string $function): static
    {
        $this->function = strtolower($function);

        return $this;
    }

    /**
     * Set the alias for this aggregate (also controls the comparison column names).
     */
    public function as(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Alias of {@see self::as()}.
     */
    public function alias(string $alias): static
    {
        return $this->as($alias);
    }

    /**
     * Use a single comparison type for this aggregate.
     */
    public function comparison(SequentialPeriodComparison|string $type): static
    {
        $this->comparisons = [$type];

        return $this;
    }

    /**
     * Use multiple comparison types for this aggregate.
     *
     * @param  list<SequentialPeriodComparison|string>  $types
     */
    public function comparisons(array $types): static
    {
        $this->comparisons = array_values($types);

        return $this;
    }

    /**
     * Disable period-over-period comparison columns for this aggregate.
     */
    public function withoutComparison(): static
    {
        $this->comparisons = [];

        return $this;
    }

    /**
     * Set the number of decimals used to round the aggregate output and its
     * comparison columns. Pass `null` to restore the default behaviour (no
     * rounding for aggregates and differences, 2 decimals for percent changes).
     */
    public function precision(?int $decimals): static
    {
        if ($decimals !== null && $decimals < 0) {
            throw new InvalidArgumentException('Aggregate precision must be a non-negative integer or null.');
        }

        $this->precision = $decimals;

        return $this;
    }

    /**
     * Format the aggregate, previous-period and comparison columns as locale
     * style number strings via PHP's `number_format()`. Setting either
     * separator enables the formatting; omit both (or pass null) to disable it.
     *
     * Examples:
     *   ->numberFormat('.', ',')   -> "13.868.830,91" (European / TR)
     *   ->numberFormat(',', '.')   -> "13,868,830.91" (US / EN)
     *   ->numberFormat(' ', ',')   -> "13 868 830,91" (FR)
     *   ->numberFormat(decimalSeparator: ',') -> "13868830,91" (no grouping, comma decimal)
     *   ->numberFormat(null, null) -> disable (default)
     */
    public function numberFormat(?string $thousandsSeparator = null, ?string $decimalSeparator = null): static
    {
        $this->thousandsSeparator = $thousandsSeparator;
        $this->decimalSeparator = $decimalSeparator;

        return $this;
    }
}
