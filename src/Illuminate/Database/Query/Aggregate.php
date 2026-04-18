<?php

namespace Illuminate\Database\Query;

/**
 * Fluent definition for a single aggregate used by
 * {@see \Illuminate\Database\Query\Builder::withSequentialPeriodMetrics()}.
 *
 * Typical usage:
 *
 *     Aggregate::sum('revenue')->as('total_revenue')->comparison(SequentialPeriodComparison::Percent)
 *     Aggregate::count('*')->as('order_count')->comparisons([SequentialPeriodComparison::Percent, 'difference'])
 *     Aggregate::avg('cost')->withoutComparison()
 */
class Aggregate
{
    /**
     * @param  list<SequentialPeriodComparison|string>  $comparisons
     */
    public function __construct(
        public string $column,
        public string $function = 'sum',
        public ?string $alias = null,
        public array $comparisons = [SequentialPeriodComparison::Percent],
    ) {
    }

    /**
     * Start a new aggregate against the given column. Function defaults to "sum".
     */
    public static function column(string $column): static
    {
        return new static($column);
    }

    public static function sum(string $column): static
    {
        return new static($column, 'sum');
    }

    public static function avg(string $column): static
    {
        return new static($column, 'avg');
    }

    public static function min(string $column): static
    {
        return new static($column, 'min');
    }

    public static function max(string $column): static
    {
        return new static($column, 'max');
    }

    public static function count(string $column = '*'): static
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
}
