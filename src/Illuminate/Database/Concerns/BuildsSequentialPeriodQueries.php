<?php

namespace Illuminate\Database\Concerns;

use Closure;
use Illuminate\Database\Query\Aggregate;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\SequentialPeriodComparison;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

trait BuildsSequentialPeriodQueries
{
    /**
     * Bucket rows by a date/datetime column, aggregate metrics per period, then add windowed period-over-period columns.
     *
     * Each aggregate is described as [column, function?, comparison?, alias?] where every slot but
     * the column is optional. A single aggregate may be passed as a flat list (or plain string),
     * while multiple aggregates are passed as a list of lists, much like {@see Builder::where()}.
     *
     * @param  string  $periodFormat  PHP date()-style format (e.g. Y-m, Y-m-d) translated per database driver.
     * @param  \Illuminate\Database\Query\Aggregate|array<int|string, mixed>|string  $aggregates  Aggregate definitions. Supported shapes:
     *                                                         - 'revenue'  (column only; function "sum", comparison Percent)
     *                                                         - ['revenue']  (same as above)
     *                                                         - ['revenue', 'avg']  (column + function)
     *                                                         - ['revenue', 'sum', SequentialPeriodComparison::Difference]  (+ comparison)
     *                                                         - ['revenue', 'sum', [SequentialPeriodComparison::Percent, 'difference']]  (+ multiple comparisons)
     *                                                         - ['revenue', 'sum', SequentialPeriodComparison::Percent, 'total_revenue']  (+ alias)
     *                                                         - ['revenue', 'sum', 'total_revenue']  (3-slot shorthand: alias goes in slot 3 when it's not a comparison)
     *                                                         - ['revenue', 'sum', false]  (no comparison for this aggregate)
     *                                                         - [['revenue'], ['cost', 'avg']]  (multiple aggregates)
     *                                                         - ['total_revenue' => ['sum', 'revenue']]  (legacy keyed form; uses default Percent)
     *                                                         - Aggregate::sum('revenue')->as('total_revenue')->comparison(...) (fluent form, may be mixed with any other shape in a list)
     *                                                         - Aggregate::sum(DB::raw('revenue * quantity'))->as('gross') (raw Expression column; alias required)
     *                                                         - Aggregate::sum(fn ($q) => $q->from('items')->selectRaw('sum(price)'))->as('gross') (Closure sub-query as column; alias required)
     * @param  string  $dateColumn  The date or datetime column to bucket (default created_at).
     * @param  string  $periodColumnAlias  Alias for the bucket column in the result set.
     * @param  bool  $includePreviousPeriodValues  When true, each aggregate gets a "{alias}_previous_period" column via LAG.
     * @param  bool  $selectComparisonsOnly  When true (default), the outer query returns only the period column and comparison columns (e.g. *_change_percent); aggregates and *_previous_period remain in subqueries for the formulas but are omitted from the selected result set.
     * @param  int|null  $precision  Default number of decimals applied to aggregate outputs, previous-period outputs and every comparison column. Per-aggregate `Aggregate::precision()` overrides this default. When null (the default), aggregate and difference columns stay unrounded and percent-change columns keep their historical 2-decimal rounding.
     * @param  string|null  $thousandsSeparator  Default thousands separator used when formatting aggregate and comparison columns as strings in PHP (e.g. "." for "13.868.830,91"). Null disables formatting. Per-aggregate `Aggregate::numberFormat()` overrides this default.
     * @param  string|null  $decimalSeparator  Default decimal separator used together with $thousandsSeparator. Either argument enables the post-query formatting.
     * @return $this
     */
    public function withSequentialPeriodMetrics(
        string $periodFormat,
        Aggregate|array|string $aggregates,
        string $dateColumn = 'created_at',
        string $periodColumnAlias = 'period',
        bool $includePreviousPeriodValues = true,
        bool $selectComparisonsOnly = true,
        ?int $precision = null,
        ?string $thousandsSeparator = null,
        ?string $decimalSeparator = null,
    ) {
        if ($precision !== null && $precision < 0) {
            throw new InvalidArgumentException('Default precision must be a non-negative integer or null.');
        }

        if (is_string($aggregates) || $aggregates instanceof Aggregate) {
            $aggregates = [$aggregates];
        }

        if ($aggregates === []) {
            throw new InvalidArgumentException('At least one aggregate definition is required.');
        }

        $this->validateSequentialPeriodDateColumn($dateColumn);

        [$aggregates, $normalizedComparisons, $aggregateFormatMap] = $this->normalizeSequentialPeriodAggregates(
            $aggregates,
            $precision,
            $thousandsSeparator,
            $decimalSeparator,
        );

        if ($normalizedComparisons !== []) {
            $includePreviousPeriodValues = true;
        }

        $grammar = $this->grammar;

        $periodExpressionSql = $grammar->compileGroupedDate($dateColumn, $periodFormat);

        $inner = $this->cloneWithoutSelectState();

        $inner->selectRaw($periodExpressionSql.' as '.$grammar->wrap($periodColumnAlias));

        foreach ($aggregates as $alias => [$function, $column, $aggregatePrecision]) {
            [$expressionSql, $expressionBindings] = $this->compileSequentialPeriodAggregateExpression(
                $function,
                $column,
                $aggregatePrecision,
            );

            $inner->selectRaw(
                $expressionSql.' as '.$grammar->wrap($alias),
                $expressionBindings,
            );
        }

        $inner->groupBy(new Expression($periodExpressionSql));

        if ($normalizedComparisons === [] && ! $includePreviousPeriodValues) {
            $this->attachSequentialPeriodFormattingCallback(
                $inner,
                $aggregateFormatMap,
                [],
                includeAggregates: true,
                includePreviousPeriodValues: false,
            );

            return $this->replaceBuilderQueryState($inner);
        }

        if ($normalizedComparisons === []) {
            $windowedOnly = $this->buildSequentialPeriodWindowQuery($inner, $aggregates, $periodColumnAlias, $includePreviousPeriodValues, 'laravel_seq_period_metrics')
                ->tap(function ($query) use ($grammar, $periodColumnAlias) {
                    $query->orderByRaw($grammar->wrapTable('laravel_seq_period_metrics').'.'.$grammar->wrap($periodColumnAlias));
                });

            $this->attachSequentialPeriodFormattingCallback(
                $windowedOnly,
                $aggregateFormatMap,
                [],
                includeAggregates: true,
                includePreviousPeriodValues: $includePreviousPeriodValues,
            );

            return $this->replaceBuilderQueryState($windowedOnly);
        }

        $aggregatedSubqueryAlias = 'laravel_seq_period_agg';

        $windowed = $this->buildSequentialPeriodWindowQuery(
            $inner,
            $aggregates,
            $periodColumnAlias,
            $includePreviousPeriodValues,
            $aggregatedSubqueryAlias,
        );

        $outerAlias = 'laravel_seq_period_metrics';

        $final = $this->newQuery()->fromSub($windowed, $outerAlias);

        $tableWrapped = $grammar->wrapTable($outerAlias);
        $periodWrapped = $grammar->wrap($periodColumnAlias);

        $final->selectRaw($tableWrapped.'.'.$periodWrapped);

        if (! $selectComparisonsOnly) {
            foreach (array_keys($aggregates) as $alias) {
                $aliasWrapped = $grammar->wrap($alias);

                $final->selectRaw($tableWrapped.'.'.$aliasWrapped);

                if ($includePreviousPeriodValues) {
                    $final->selectRaw($tableWrapped.'.'.$grammar->wrap($alias.'_previous_period'));
                }
            }
        }

        foreach ($normalizedComparisons as $comparison) {
            $column = $comparison['column'];
            $type = SequentialPeriodComparison::from($comparison['type']);
            $as = $comparison['as'] ?? $this->defaultSequentialPeriodComparisonAlias($column, $type->value);
            $comparisonPrecision = $comparison['precision'] ?? null;

            $current = $tableWrapped.'.'.$grammar->wrap($column);
            $previous = $tableWrapped.'.'.$grammar->wrap($column.'_previous_period');

            match ($type) {
                SequentialPeriodComparison::Percent => $final->selectRaw(
                    'round((('.$current.' - '.$previous.') / nullif('.$previous.', 0)) * 100, '.($comparisonPrecision ?? 2).') as '.$grammar->wrap($as)
                ),
                SequentialPeriodComparison::Difference => $final->selectRaw(
                    ($comparisonPrecision === null
                        ? '('.$current.' - '.$previous.')'
                        : 'round('.$current.' - '.$previous.', '.$comparisonPrecision.')'
                    ).' as '.$grammar->wrap($as)
                ),
            };
        }

        $final->orderByRaw($tableWrapped.'.'.$periodWrapped);

        $this->attachSequentialPeriodFormattingCallback(
            $final,
            $aggregateFormatMap,
            $normalizedComparisons,
            includeAggregates: ! $selectComparisonsOnly,
            includePreviousPeriodValues: ! $selectComparisonsOnly && $includePreviousPeriodValues,
        );

        return $this->replaceBuilderQueryState($final);
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $aggregates
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildSequentialPeriodWindowQuery($inner, array $aggregates, string $periodColumnAlias, bool $includePreviousPeriodValues, string $subqueryAlias)
    {
        $grammar = $this->grammar;

        $query = $this->newQuery()->fromSub($inner, $subqueryAlias);

        $tableWrapped = $grammar->wrapTable($subqueryAlias);
        $periodWrapped = $grammar->wrap($periodColumnAlias);

        $query->selectRaw($tableWrapped.'.'.$periodWrapped);

        foreach (array_keys($aggregates) as $alias) {
            $aliasWrapped = $grammar->wrap($alias);

            $query->selectRaw($tableWrapped.'.'.$aliasWrapped);

            if ($includePreviousPeriodValues) {
                $previousAlias = $alias.'_previous_period';

                $query->selectRaw(
                    'lag('.$tableWrapped.'.'.$aliasWrapped.') over (order by '.$tableWrapped.'.'.$periodWrapped.') as '.$grammar->wrap($previousAlias)
                );
            }
        }

        return $query;
    }

    /**
     * @return $this
     */
    protected function cloneWithoutSelectState()
    {
        $inner = $this->clone();

        $inner->aggregate = null;
        $inner->columns = [];
        $inner->distinct = false;
        $inner->groups = null;
        $inner->havings = null;
        $inner->orders = null;
        $inner->limit = null;
        $inner->offset = null;
        $inner->unions = null;
        $inner->unionLimit = null;
        $inner->unionOffset = null;
        $inner->unionOrders = null;
        $inner->groupLimit = null;
        $inner->bindings['select'] = [];
        $inner->bindings['groupBy'] = [];
        $inner->bindings['having'] = [];
        $inner->bindings['order'] = [];
        $inner->bindings['union'] = [];
        $inner->bindings['unionOrder'] = [];

        return $inner;
    }

    /**
     * @return $this
     */
    protected function replaceBuilderQueryState(self $source)
    {
        $this->aggregate = $source->aggregate;
        $this->columns = $source->columns;
        $this->distinct = $source->distinct;
        $this->from = $source->from;
        $this->indexHint = $source->indexHint;
        $this->joins = $source->joins;
        $this->wheres = $source->wheres;
        $this->groups = $source->groups;
        $this->havings = $source->havings;
        $this->orders = $source->orders;
        $this->limit = $source->limit;
        $this->offset = $source->offset;
        $this->unions = $source->unions;
        $this->unionLimit = $source->unionLimit;
        $this->unionOffset = $source->unionOffset;
        $this->unionOrders = $source->unionOrders;
        $this->lock = $source->lock;
        $this->timeout = $source->timeout;
        $this->bindings = $source->bindings;
        $this->beforeQueryCallbacks = $source->beforeQueryCallbacks;
        $this->afterQueryCallbacks = $source->afterQueryCallbacks;
        $this->groupLimit = $source->groupLimit;
        $this->useWritePdo = $source->useWritePdo;
        $this->fetchUsing = $source->fetchUsing;

        return $this;
    }

    /**
     * Normalize the user-provided aggregate definitions.
     *
     * @param  array<int|string, mixed>  $aggregates
     * @return array{0: array<string, array{0: string, 1: \Closure|\Illuminate\Database\Query\Expression|string, 2: ?int}>, 1: list<array{column: string, type: string, as: ?string, precision: ?int}>, 2: array<string, array{precision: ?int, thousands: ?string, decimal: ?string}>}
     */
    protected function normalizeSequentialPeriodAggregates(
        array $aggregates,
        ?int $defaultPrecision = null,
        ?string $defaultThousandsSeparator = null,
        ?string $defaultDecimalSeparator = null,
    ): array {
        $entries = $this->isSinglePositionalAggregate($aggregates)
            ? [$this->parseSequentialPeriodPositionalAggregate($aggregates, $defaultPrecision, $defaultThousandsSeparator, $defaultDecimalSeparator)]
            : array_map(
                fn ($definition, $key) => $this->parseSequentialPeriodAggregateEntry(
                    $key,
                    $definition,
                    $defaultPrecision,
                    $defaultThousandsSeparator,
                    $defaultDecimalSeparator,
                ),
                $aggregates,
                array_keys($aggregates),
            );

        $normalizedAggregates = [];
        $normalizedComparisons = [];
        $aggregateFormatMap = [];

        foreach ($entries as $entry) {
            $alias = $entry['alias'];

            if (isset($normalizedAggregates[$alias])) {
                throw new InvalidArgumentException("Duplicate aggregate alias [{$alias}].");
            }

            $normalizedAggregates[$alias] = [$entry['function'], $entry['column'], $entry['precision']];

            $aggregateFormatMap[$alias] = [
                'precision' => $entry['precision'],
                'thousands' => $entry['thousands_separator'] ?? null,
                'decimal' => $entry['decimal_separator'] ?? null,
            ];

            foreach ($entry['comparisons'] as $type) {
                $normalizedComparisons[] = [
                    'column' => $alias,
                    'type' => $type,
                    'as' => null,
                    'precision' => $entry['precision'],
                ];
            }
        }

        return [$normalizedAggregates, $normalizedComparisons, $aggregateFormatMap];
    }

    /**
     * Determine if the outer aggregates array represents a single positional entry.
     *
     * A single positional entry starts with a column (string, Expression, or
     * Closure) at index 0 (e.g. ['revenue', 'sum', ...]). When the outer array
     * begins with a nested array or uses string keys, it is treated as a list
     * of entries instead.
     *
     * As a convenience, when the array consists entirely of strings (e.g.
     * ['revenue', 'cost']) it is treated as a list of column-only shorthands
     * unless the second element is one of the recognised aggregate function
     * names (sum, avg, min, max, count), which would otherwise be ambiguous
     * with the [column, function, ...] positional form.
     *
     * @param  array<int|string, mixed>  $aggregates
     */
    protected function isSinglePositionalAggregate(array $aggregates): bool
    {
        if (! array_key_exists(0, $aggregates)) {
            return false;
        }

        foreach ($aggregates as $key => $value) {
            if (is_string($key)) {
                return false;
            }

            if ($value instanceof Aggregate) {
                return false;
            }
        }

        $first = $aggregates[0];

        if (! is_string($first) && ! ($first instanceof Expression) && ! ($first instanceof Closure)) {
            return false;
        }

        if (count($aggregates) === 1) {
            return true;
        }

        if ($first instanceof Expression || $first instanceof Closure) {
            return true;
        }

        $second = $aggregates[1];

        if (is_string($second)) {
            return in_array(strtolower($second), ['sum', 'avg', 'min', 'max', 'count'], true);
        }

        return true;
    }

    /**
     * Parse one aggregate entry within a multi-form list.
     *
     * @param  int|string  $key
     * @return array{column: \Closure|\Illuminate\Database\Query\Expression|string, function: string, alias: string, comparisons: list<string>, precision: ?int, thousands_separator: ?string, decimal_separator: ?string}
     */
    protected function parseSequentialPeriodAggregateEntry(
        int|string $key,
        mixed $definition,
        ?int $defaultPrecision = null,
        ?string $defaultThousandsSeparator = null,
        ?string $defaultDecimalSeparator = null,
    ): array {
        if (is_string($key)) {
            return $this->parseSequentialPeriodLegacyKeyedAggregate($key, $definition, $defaultPrecision, $defaultThousandsSeparator, $defaultDecimalSeparator);
        }

        if ($definition instanceof Aggregate) {
            return $this->parseSequentialPeriodFluentAggregate($definition, $defaultPrecision, $defaultThousandsSeparator, $defaultDecimalSeparator);
        }

        if (is_string($definition) || $definition instanceof Expression || $definition instanceof Closure) {
            $definition = [$definition];
        }

        if (! is_array($definition)) {
            throw new InvalidArgumentException('Aggregate entry must be a column, an Aggregate instance, or a [column, function?, comparison?, alias?] array.');
        }

        return $this->parseSequentialPeriodPositionalAggregate($definition, $defaultPrecision, $defaultThousandsSeparator, $defaultDecimalSeparator);
    }

    /**
     * Convert a fluent Aggregate instance into the normalized internal shape.
     *
     * @return array{column: \Closure|\Illuminate\Database\Query\Expression|string, function: string, alias: string, comparisons: list<string>, precision: ?int, thousands_separator: ?string, decimal_separator: ?string}
     */
    protected function parseSequentialPeriodFluentAggregate(
        Aggregate $aggregate,
        ?int $defaultPrecision = null,
        ?string $defaultThousandsSeparator = null,
        ?string $defaultDecimalSeparator = null,
    ): array {
        $column = $aggregate->column;
        $function = strtolower($aggregate->function);

        $this->ensureSequentialPeriodAggregateColumnIsUsable($column);

        $alias = $aggregate->alias ?? $this->defaultSequentialPeriodAggregateAlias($column, $function);

        if ($alias === '') {
            throw new InvalidArgumentException('Aggregate alias must be a non-empty string.');
        }

        return [
            'column' => $column,
            'function' => $function,
            'alias' => $alias,
            'comparisons' => $this->normalizeSequentialPeriodEntryComparisons($aggregate->comparisons),
            'precision' => $aggregate->precision ?? $defaultPrecision,
            'thousands_separator' => $aggregate->thousandsSeparator ?? $defaultThousandsSeparator,
            'decimal_separator' => $aggregate->decimalSeparator ?? $defaultDecimalSeparator,
        ];
    }

    /**
     * Parse a positional aggregate definition: [column, function?, comparison?, alias?].
     *
     * @param  array<int|string, mixed>  $definition
     * @return array{column: \Closure|\Illuminate\Database\Query\Expression|string, function: string, alias: string, comparisons: list<string>, precision: ?int, thousands_separator: ?string, decimal_separator: ?string}
     */
    protected function parseSequentialPeriodPositionalAggregate(
        array $definition,
        ?int $defaultPrecision = null,
        ?string $defaultThousandsSeparator = null,
        ?string $defaultDecimalSeparator = null,
    ): array {
        $values = array_values($definition);
        $count = count($values);

        if ($count < 1 || $count > 4) {
            throw new InvalidArgumentException('Aggregate definition must be [column, function?, comparison?, alias?].');
        }

        $column = $values[0];

        $this->ensureSequentialPeriodAggregateColumnIsUsable($column);

        $function = $values[1] ?? 'sum';

        if (! is_string($function)) {
            throw new InvalidArgumentException('Aggregate function must be a string.');
        }

        $function = strtolower($function);

        $slot2 = array_key_exists(2, $values) ? $values[2] : null;
        $slot3 = array_key_exists(3, $values) ? $values[3] : null;

        if ($slot3 !== null) {
            $comparison = $slot2;
            $alias = $slot3;
        } elseif ($count < 3) {
            $comparison = SequentialPeriodComparison::Percent;
            $alias = null;
        } elseif ($this->looksLikeSequentialPeriodComparisonValue($slot2)) {
            $comparison = $slot2;
            $alias = null;
        } else {
            $comparison = SequentialPeriodComparison::Percent;
            $alias = $slot2;
        }

        if ($alias !== null && ! is_string($alias)) {
            throw new InvalidArgumentException('Aggregate alias must be a string.');
        }

        $alias ??= $this->defaultSequentialPeriodAggregateAlias($column, $function);

        if ($alias === '') {
            throw new InvalidArgumentException('Aggregate alias must be a non-empty string.');
        }

        return [
            'column' => $column,
            'function' => $function,
            'alias' => $alias,
            'comparisons' => $this->normalizeSequentialPeriodEntryComparisons($comparison),
            'precision' => $defaultPrecision,
            'thousands_separator' => $defaultThousandsSeparator,
            'decimal_separator' => $defaultDecimalSeparator,
        ];
    }

    /**
     * Parse the legacy 'alias' => [function, column] aggregate entry.
     *
     * @return array{column: \Closure|\Illuminate\Database\Query\Expression|string, function: string, alias: string, comparisons: list<string>, precision: ?int, thousands_separator: ?string, decimal_separator: ?string}
     */
    protected function parseSequentialPeriodLegacyKeyedAggregate(
        string $alias,
        mixed $definition,
        ?int $defaultPrecision = null,
        ?string $defaultThousandsSeparator = null,
        ?string $defaultDecimalSeparator = null,
    ): array {
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Aggregate [{$alias}] must be a [function, column] pair.");
        }

        $values = array_values($definition);

        if (count($values) !== 2 || ! is_string($values[0])) {
            throw new InvalidArgumentException("Aggregate [{$alias}] must be a [function, column] pair.");
        }

        $column = $values[1];

        if (! is_string($column) && ! ($column instanceof Expression) && ! ($column instanceof Closure)) {
            throw new InvalidArgumentException("Aggregate [{$alias}] must be a [function, column] pair.");
        }

        $this->ensureSequentialPeriodAggregateColumnIsUsable($column);

        return [
            'column' => $column,
            'function' => strtolower($values[0]),
            'alias' => $alias,
            'comparisons' => [SequentialPeriodComparison::Percent->value],
            'precision' => $defaultPrecision,
            'thousands_separator' => $defaultThousandsSeparator,
            'decimal_separator' => $defaultDecimalSeparator,
        ];
    }

    /**
     * Decide whether the value in positional slot 2 looks like a comparison specifier
     * (as opposed to an explicit alias string).
     */
    protected function looksLikeSequentialPeriodComparisonValue(mixed $value): bool
    {
        if ($value === false || $value === null || $value === []) {
            return true;
        }

        if ($value instanceof SequentialPeriodComparison) {
            return true;
        }

        if (is_string($value)) {
            return SequentialPeriodComparison::tryFrom(strtolower($value)) !== null;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! ($item instanceof SequentialPeriodComparison) && ! is_string($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Normalize a single aggregate's comparison input into a list of enum string values.
     *
     * @return list<string>
     */
    protected function normalizeSequentialPeriodEntryComparisons(mixed $comparison): array
    {
        if ($comparison === false || $comparison === null || $comparison === []) {
            return [];
        }

        if (is_string($comparison) || $comparison instanceof SequentialPeriodComparison) {
            return [$this->parseSequentialPeriodComparisonType($comparison)];
        }

        if (is_array($comparison)) {
            return array_map(
                fn ($type) => $this->parseSequentialPeriodComparisonType($type),
                array_values($comparison),
            );
        }

        throw new InvalidArgumentException('Invalid aggregate comparison value.');
    }

    /**
     * @param  string|SequentialPeriodComparison  $type
     */
    protected function parseSequentialPeriodComparisonType(string|SequentialPeriodComparison $type): string
    {
        if ($type instanceof SequentialPeriodComparison) {
            return $type->value;
        }

        $enum = SequentialPeriodComparison::tryFrom(strtolower($type));

        if ($enum === null) {
            throw new InvalidArgumentException("Unsupported sequential period comparison type [{$type}].");
        }

        return $enum->value;
    }

    /**
     * Ensure the given aggregate column value is a supported type and, when
     * provided as a string, non-empty.
     */
    protected function ensureSequentialPeriodAggregateColumnIsUsable(mixed $column): void
    {
        if ($column instanceof Expression || $column instanceof Closure) {
            return;
        }

        if (! is_string($column) || $column === '') {
            throw new InvalidArgumentException('Aggregate column must be a non-empty string, an Expression, or a Closure.');
        }
    }

    /**
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     */
    protected function defaultSequentialPeriodAggregateAlias(Closure|Expression|string $column, string $function): string
    {
        $function = strtolower($function);

        if ($column instanceof Closure || $column instanceof Expression) {
            throw new InvalidArgumentException(
                'An explicit alias is required when the aggregate column is an Expression or Closure.'
            );
        }

        return $column === '*' ? $function : $column.'_'.$function;
    }

    /**
     * Compile an aggregate expression into raw SQL plus any bindings produced
     * by a Closure sub-query column.
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     * @return array{0: string, 1: array}
     */
    protected function compileSequentialPeriodAggregateExpression(string $function, Closure|Expression|string $column, ?int $precision = null): array
    {
        if (! in_array($function, ['sum', 'avg', 'min', 'max', 'count'], true)) {
            throw new InvalidArgumentException("Unsupported aggregate function [{$function}].");
        }

        [$columnSql, $bindings] = $this->resolveSequentialPeriodAggregateColumn($function, $column);

        if ($function === 'count' && is_string($column) && $column === '*') {
            $expression = 'count(*)';
            $bindings = [];
        } else {
            $expression = $function.'('.$columnSql.')';
        }

        if ($precision !== null) {
            $expression = 'round('.$expression.', '.$precision.')';
        }

        return [$expression, $bindings];
    }

    /**
     * Resolve an aggregate column into its SQL fragment and bindings.
     *
     * @param  \Closure|\Illuminate\Database\Query\Expression|string  $column
     * @return array{0: string, 1: array}
     */
    protected function resolveSequentialPeriodAggregateColumn(string $function, Closure|Expression|string $column): array
    {
        if ($column instanceof Expression) {
            return [(string) $this->grammar->getValue($column), []];
        }

        if ($column instanceof Closure) {
            $subQuery = $this->forSubQuery();

            $column($subQuery);

            return ['('.$subQuery->toSql().')', $subQuery->getBindings()];
        }

        if ($column === '*' && $function !== 'count') {
            throw new InvalidArgumentException("Aggregate column [*] is only supported with the count() function, got [{$function}].");
        }

        return [$this->grammar->wrap($column), []];
    }

    /**
     * Attach a post-query callback to $builder that rewrites the configured
     * output columns using PHP's {@see number_format()}. Uses the per-aggregate
     * thousands / decimal separators captured during normalization. If none of
     * the aggregates request formatting, no callback is attached.
     *
     * @param  array<string, array{precision: ?int, thousands: ?string, decimal: ?string}>  $aggregateFormatMap
     * @param  list<array{column: string, type: string, as: ?string, precision: ?int}>  $normalizedComparisons
     */
    protected function attachSequentialPeriodFormattingCallback(
        $builder,
        array $aggregateFormatMap,
        array $normalizedComparisons,
        bool $includeAggregates,
        bool $includePreviousPeriodValues,
    ): void {
        $columnFormats = [];

        foreach ($aggregateFormatMap as $alias => $format) {
            if (! $this->sequentialPeriodFormatIsActive($format)) {
                continue;
            }

            if ($includeAggregates) {
                $columnFormats[$alias] = $format;
            }

            if ($includePreviousPeriodValues) {
                $columnFormats[$alias.'_previous_period'] = $format;
            }
        }

        foreach ($normalizedComparisons as $comparison) {
            $alias = $comparison['column'];
            $format = $aggregateFormatMap[$alias] ?? null;

            if ($format === null || ! $this->sequentialPeriodFormatIsActive($format)) {
                continue;
            }

            $type = SequentialPeriodComparison::from($comparison['type']);
            $columnName = $comparison['as'] ?? $this->defaultSequentialPeriodComparisonAlias($alias, $type->value);

            $columnFormats[$columnName] = [
                'precision' => $comparison['precision'] ?? $format['precision'],
                'thousands' => $format['thousands'],
                'decimal' => $format['decimal'],
            ];
        }

        if ($columnFormats === []) {
            return;
        }

        $builder->afterQuery(static function ($results) use ($columnFormats) {
            return static::formatSequentialPeriodResults($results, $columnFormats);
        });
    }

    /**
     * Whether the given format spec should trigger PHP post-processing.
     *
     * @param  array{precision: ?int, thousands: ?string, decimal: ?string}  $format
     */
    protected function sequentialPeriodFormatIsActive(array $format): bool
    {
        return $format['thousands'] !== null || $format['decimal'] !== null;
    }

    /**
     * Apply the configured column formats to a result set returned from the
     * query builder. Accepts Collections of either stdClass rows or associative
     * arrays; any other shape (scalars, null, already paginated payloads) is
     * returned unchanged.
     *
     * @param  array<string, array{precision: ?int, thousands: ?string, decimal: ?string}>  $columnFormats
     */
    protected static function formatSequentialPeriodResults(mixed $results, array $columnFormats): mixed
    {
        if (! $results instanceof Collection) {
            return $results;
        }

        return $results->map(function ($row) use ($columnFormats) {
            if (is_object($row)) {
                foreach ($columnFormats as $column => $format) {
                    if (! property_exists($row, $column)) {
                        continue;
                    }

                    $row->{$column} = static::formatSequentialPeriodValue($row->{$column}, $format);
                }

                return $row;
            }

            if (is_array($row)) {
                foreach ($columnFormats as $column => $format) {
                    if (! array_key_exists($column, $row)) {
                        continue;
                    }

                    $row[$column] = static::formatSequentialPeriodValue($row[$column], $format);
                }

                return $row;
            }

            return $row;
        });
    }

    /**
     * Format a single numeric value using the supplied format spec. Non-numeric
     * and null values are returned unchanged.
     *
     * @param  array{precision: ?int, thousands: ?string, decimal: ?string}  $format
     */
    protected static function formatSequentialPeriodValue(mixed $value, array $format): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! is_numeric($value)) {
            return $value;
        }

        $thousands = $format['thousands'] ?? '';
        $decimal = $format['decimal'] ?? '.';

        $decimals = $format['precision'];

        if ($decimals === null) {
            $stringValue = (string) $value;

            $decimals = str_contains($stringValue, '.')
                ? strlen(explode('.', $stringValue)[1])
                : 0;
        }

        return number_format((float) $value, $decimals, $decimal, $thousands);
    }

    /**
     * Ensure the configured bucket column is actually a date/datetime type in the
     * underlying table. When the schema type cannot be resolved (e.g. mocked
     * connection, sub-query source, virtual tables) the check is skipped.
     */
    protected function validateSequentialPeriodDateColumn(string $dateColumn): void
    {
        $table = $this->resolveSequentialPeriodTableName();

        if ($table === null) {
            return;
        }

        $column = $dateColumn;

        if (str_contains($column, '.')) {
            [, $column] = explode('.', $column, 2);
        }

        $type = $this->resolveSequentialPeriodColumnType($table, $column);

        if ($type === null) {
            return;
        }

        if (! $this->isSequentialPeriodDateLikeColumnType($type)) {
            throw new InvalidArgumentException(
                "Date column [{$dateColumn}] on table [{$table}] must be a date or datetime type, got [{$type}]."
            );
        }
    }

    /**
     * Extract the raw table name (without alias) from the current "from" clause,
     * or null when the source is dynamic (Expression, sub-query, etc.).
     */
    protected function resolveSequentialPeriodTableName(): ?string
    {
        $from = $this->from;

        if (! is_string($from) || $from === '') {
            return null;
        }

        $table = preg_replace('/\s+as\s+.+$/i', '', $from);

        return is_string($table) && $table !== '' ? trim($table) : null;
    }

    /**
     * Best-effort lookup of a column's schema type for the given table. Any
     * schema/connection failure is swallowed so the feature stays usable in
     * environments where introspection is unavailable.
     */
    protected function resolveSequentialPeriodColumnType(string $table, string $column): ?string
    {
        try {
            $type = $this->connection->getSchemaBuilder()->getColumnType($table, $column);
        } catch (Throwable) {
            return null;
        }

        return is_string($type) && $type !== '' ? strtolower($type) : null;
    }

    /**
     * Whitelist of column types that may legitimately be bucketed into periods.
     */
    protected function isSequentialPeriodDateLikeColumnType(string $type): bool
    {
        return in_array($type, [
            'date',
            'datetime',
            'datetimetz',
            'timestamp',
            'timestamptz',
        ], true);
    }

    protected function defaultSequentialPeriodComparisonAlias(string $column, string $type): string
    {
        return match (SequentialPeriodComparison::from($type)) {
            SequentialPeriodComparison::Percent => $column.'_change_percent',
            SequentialPeriodComparison::Difference => $column.'_change',
        };
    }
}
