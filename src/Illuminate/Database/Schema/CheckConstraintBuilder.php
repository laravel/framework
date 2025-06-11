<?php

namespace Illuminate\Database\Schema;

use Closure;
class CheckConstraintBuilder
{
    /**
     * The constraint groups.
     *
     * @var array<int, array<string, string>>
     */
    protected array $groups = [];

    /**
     * The constraint expressions.
     *
     * @var array<int, string>
     */
    protected array $expressions = [];

    /**
     * @param  string  $name
     */
    public function __construct(
        protected string $name,
    ) {

    }

    /**
     * Get the constraint name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Add a new constraint expression and group it with an AND operator.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  Closure  $callback
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value, Closure $callback): static
    {
        return $this->addGroup('AND', $column, $operator, $value, $callback);
    }

    /**
     * Add a new constraint expression and group it with an OR operator.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  Closure  $callback
     * @return $this
     */
    public function orWhere(string $column, string $operator, mixed $value, Closure $callback): static
    {
        return $this->addGroup('OR', $column, $operator, $value, $callback);
    }

    /**
     * Add a new constraint expression and group it with the given glue operator.
     *
     * @param  string  $glue
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  Closure  $callback
     * @return $this
     */
    protected function addGroup(string $glue, string $column, string $operator, mixed $value, Closure $callback): static
    {
        $groupBuilder = new static('_inline_'); // temp inner builder
        $callback($groupBuilder);

        $this->groups[] = [
            'glue' => $glue,
            'condition' => "$column $operator ".$this->quote($value),
            'expressions' => $groupBuilder->expressions,
        ];

        return $this;
    }

    /**
     * Add a new constraint expression to the builder.
     *
     * @param  string  $column
     * @param  array  $values
     * @return $this
     */
    public function whereNotIn(string $column, array $values): static
    {
        $quoted = implode(', ', array_map([$this, 'quote'], $values));
        $this->expressions[] = "$column NOT IN ($quoted)";

        return $this;
    }

    /**
     * Add a new constraint expression to the builder.
     *
     * @param  string  $column
     * @param  array  $values
     * @return $this
     */
    public function whereIn(string $column, array $values): static
    {
        $quoted = implode(', ', array_map([$this, 'quote'], $values));
        $this->expressions[] = "$column IN ($quoted)";

        return $this;
    }

    /**
     * Compile the check constraint to SQL.
     *
     * @return string
     */
    public function toSql(): string
    {
        $parts = [];

        // Handle groups (from where/orWhere)
        foreach ($this->groups as $index => $group) {
            $expr = implode(' AND ', $group['expressions']);
            $logic = $index > 0 ? $group['glue'].' ' : '';
            $parts[] = $logic."({$group['condition']} AND {$expr})";
        }

        // Handle standalone expressions (from whereIn, whereNotIn, rule)
        if (! empty($this->expressions) && empty($this->groups)) {
            $parts[] = implode(' AND ', $this->expressions);
        }

        return 'CHECK ('.implode(' ', $parts).')';
    }

    /**
     * Quote the given value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function quote(mixed $value): string
    {
        if (is_string($value)) {
            return "'".str_replace("'", "''", $value)."'";
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_null($value)) {
            return 'NULL';
        }

        return (string) $value;
    }

    /**
     * Add a new rule to the constraint.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed|null  $value
     * @return $this
     */
    public function rule(string $column, string $operator, mixed $value = null): static
    {
        $operator = strtoupper(trim($operator));
        $expression = "$column $operator";

        if (! str_ends_with($operator, 'NULL')) {
            $expression .= ' '.$this->quote($value);
        }

        $this->expressions[] = $expression;

        return $this;
    }
}
