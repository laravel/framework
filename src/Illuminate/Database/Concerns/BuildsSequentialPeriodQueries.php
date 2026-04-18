<?php

namespace Illuminate\Database\Concerns;

use Illuminate\Database\Query\Aggregate;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\SequentialPeriodComparison;
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
     * @param  string  $dateColumn  The date or datetime column to bucket (default created_at).
     * @param  string  $periodColumnAlias  Alias for the bucket column in the result set.
     * @param  bool  $includePreviousPeriodValues  When true, each aggregate gets a "{alias}_previous_period" column via LAG.
     * @param  bool  $selectComparisonsOnly  When true (default), the outer query returns only the period column and comparison columns (e.g. *_change_percent); aggregates and *_previous_period remain in subqueries for the formulas but are omitted from the selected result set.
     * @return $this
     */
    public function withSequentialPeriodMetrics(
        string $periodFormat,
        Aggregate|array|string $aggregates,
        string $dateColumn = 'created_at',
        string $periodColumnAlias = 'period',
        bool $includePreviousPeriodValues = true,
        bool $selectComparisonsOnly = true,
    ) {
        if (is_string($aggregates) || $aggregates instanceof Aggregate) {
            $aggregates = [$aggregates];
        }

        if ($aggregates === []) {
            throw new InvalidArgumentException('At least one aggregate definition is required.');
        }

        $this->validateSequentialPeriodDateColumn($dateColumn);

        [$aggregates, $normalizedComparisons] = $this->normalizeSequentialPeriodAggregates($aggregates);

        if ($normalizedComparisons !== []) {
            $includePreviousPeriodValues = true;
        }

        $grammar = $this->grammar;

        $periodExpressionSql = $grammar->compileGroupedDate($dateColumn, $periodFormat);

        $inner = $this->cloneWithoutSelectState();

        $inner->selectRaw($periodExpressionSql.' as '.$grammar->wrap($periodColumnAlias));

        foreach ($aggregates as $alias => [$function, $column]) {
            $inner->selectRaw(
                $this->compileSequentialPeriodAggregateExpression($function, $column).' as '.$grammar->wrap($alias)
            );
        }

        $inner->groupBy(new Expression($periodExpressionSql));

        if ($normalizedComparisons === [] && ! $includePreviousPeriodValues) {
            return $this->replaceBuilderQueryState($inner);
        }

        if ($normalizedComparisons === []) {
            return $this->replaceBuilderQueryState(
                $this->buildSequentialPeriodWindowQuery($inner, $aggregates, $periodColumnAlias, $includePreviousPeriodValues, 'laravel_seq_period_metrics')
                    ->tap(function ($query) use ($grammar, $periodColumnAlias) {
                        $query->orderByRaw($grammar->wrapTable('laravel_seq_period_metrics').'.'.$grammar->wrap($periodColumnAlias));
                    })
            );
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

            $current = $tableWrapped.'.'.$grammar->wrap($column);
            $previous = $tableWrapped.'.'.$grammar->wrap($column.'_previous_period');

            match ($type) {
                SequentialPeriodComparison::Percent => $final->selectRaw(
                    'round((('.$current.' - '.$previous.') / nullif('.$previous.', 0)) * 100, 2) as '.$grammar->wrap($as)
                ),
                SequentialPeriodComparison::Difference => $final->selectRaw(
                    '('.$current.' - '.$previous.') as '.$grammar->wrap($as)
                ),
            };
        }

        $final->orderByRaw($tableWrapped.'.'.$periodWrapped);

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
     * @return array{0: array<string, array{0: string, 1: string}>, 1: list<array{column: string, type: string, as: ?string}>}
     */
    protected function normalizeSequentialPeriodAggregates(array $aggregates): array
    {
        $entries = $this->isSinglePositionalAggregate($aggregates)
            ? [$this->parseSequentialPeriodPositionalAggregate($aggregates)]
            : array_map(
                fn ($definition, $key) => $this->parseSequentialPeriodAggregateEntry($key, $definition),
                $aggregates,
                array_keys($aggregates),
            );

        $normalizedAggregates = [];
        $normalizedComparisons = [];

        foreach ($entries as $entry) {
            $alias = $entry['alias'];

            if (isset($normalizedAggregates[$alias])) {
                throw new InvalidArgumentException("Duplicate aggregate alias [{$alias}].");
            }

            $normalizedAggregates[$alias] = [$entry['function'], $entry['column']];

            foreach ($entry['comparisons'] as $type) {
                $normalizedComparisons[] = [
                    'column' => $alias,
                    'type' => $type,
                    'as' => null,
                ];
            }
        }

        return [$normalizedAggregates, $normalizedComparisons];
    }

    /**
     * Determine if the outer aggregates array represents a single positional entry.
     *
     * A single positional entry always starts with a string column at index 0
     * (e.g. ['revenue', 'sum', ...]). When the outer array begins with a nested
     * array or uses string keys, it is treated as a list of entries instead.
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

        if (! is_string($aggregates[0])) {
            return false;
        }

        if (count($aggregates) === 1) {
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
     * @return array{column: string, function: string, alias: string, comparisons: list<string>}
     */
    protected function parseSequentialPeriodAggregateEntry(int|string $key, mixed $definition): array
    {
        if (is_string($key)) {
            return $this->parseSequentialPeriodLegacyKeyedAggregate($key, $definition);
        }

        if ($definition instanceof Aggregate) {
            return $this->parseSequentialPeriodFluentAggregate($definition);
        }

        if (is_string($definition)) {
            $definition = [$definition];
        }

        if (! is_array($definition)) {
            throw new InvalidArgumentException('Aggregate entry must be a column name, an Aggregate instance, or a [column, function?, comparison?, alias?] array.');
        }

        return $this->parseSequentialPeriodPositionalAggregate($definition);
    }

    /**
     * Convert a fluent Aggregate instance into the normalized internal shape.
     *
     * @return array{column: string, function: string, alias: string, comparisons: list<string>}
     */
    protected function parseSequentialPeriodFluentAggregate(Aggregate $aggregate): array
    {
        $column = $aggregate->column;
        $function = strtolower($aggregate->function);

        if ($column === '') {
            throw new InvalidArgumentException('Aggregate column must be a non-empty string.');
        }

        $alias = $aggregate->alias ?? $this->defaultSequentialPeriodAggregateAlias($column, $function);

        if ($alias === '') {
            throw new InvalidArgumentException('Aggregate alias must be a non-empty string.');
        }

        return [
            'column' => $column,
            'function' => $function,
            'alias' => $alias,
            'comparisons' => $this->normalizeSequentialPeriodEntryComparisons($aggregate->comparisons),
        ];
    }

    /**
     * Parse a positional aggregate definition: [column, function?, comparison?, alias?].
     *
     * @param  array<int|string, mixed>  $definition
     * @return array{column: string, function: string, alias: string, comparisons: list<string>}
     */
    protected function parseSequentialPeriodPositionalAggregate(array $definition): array
    {
        $values = array_values($definition);
        $count = count($values);

        if ($count < 1 || $count > 4) {
            throw new InvalidArgumentException('Aggregate definition must be [column, function?, comparison?, alias?].');
        }

        $column = $values[0];

        if (! is_string($column) || $column === '') {
            throw new InvalidArgumentException('Aggregate column must be a non-empty string.');
        }

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
        ];
    }

    /**
     * Parse the legacy 'alias' => [function, column] aggregate entry.
     *
     * @return array{column: string, function: string, alias: string, comparisons: list<string>}
     */
    protected function parseSequentialPeriodLegacyKeyedAggregate(string $alias, mixed $definition): array
    {
        if (! is_array($definition)) {
            throw new InvalidArgumentException("Aggregate [{$alias}] must be a [function, column] pair.");
        }

        $values = array_values($definition);

        if (count($values) !== 2 || ! is_string($values[0]) || ! is_string($values[1])) {
            throw new InvalidArgumentException("Aggregate [{$alias}] must be a [function, column] pair.");
        }

        return [
            'column' => $values[1],
            'function' => strtolower($values[0]),
            'alias' => $alias,
            'comparisons' => [SequentialPeriodComparison::Percent->value],
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

    protected function defaultSequentialPeriodAggregateAlias(string $column, string $function): string
    {
        $function = strtolower($function);

        return $column === '*' ? $function : $column.'_'.$function;
    }

    protected function compileSequentialPeriodAggregateExpression(string $function, string $column): string
    {
        if ($column === '*' && $function !== 'count') {
            throw new InvalidArgumentException("Aggregate column [*] is only supported with the count() function, got [{$function}].");
        }

        return match ($function) {
            'sum', 'avg', 'min', 'max' => $function.'('.$this->grammar->wrap($column).')',
            'count' => $column === '*' ? 'count(*)' : 'count('.$this->grammar->wrap($column).')',
            default => throw new InvalidArgumentException("Unsupported aggregate function [{$function}]."),
        };
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
