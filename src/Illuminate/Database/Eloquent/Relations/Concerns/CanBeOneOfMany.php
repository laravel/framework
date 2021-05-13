<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

trait CanBeOneOfMany
{
    /**
     * Determines wether the relationship is one-of-many.
     *
     * @var bool
     */
    protected $isOneOfMany = false;

    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $relationName;

    /**
     * Wether the relation is a partial of a one-to-many relationship.
     *
     * @param  string|array|null $column
     * @param  string|Closure|null $aggregate
     * @param  string|null $relation
     * @return $this
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = null)
    {
        $this->isOneOfMany = true;

        if (is_null($this->relationName = $relation)) {
            $this->relationName = $this->guessRelationship();
        }

        $keyName = $this->query->getModel()->getKeyName();
        
        if (is_string($columns = $column)) {
            $columns = [
                $column => $aggregate,
                $keyName => $aggregate
            ];
        }

        if ($aggregate instanceof Closure) {
            $closure = $aggregate;
        }

        foreach ($columns as $column => $aggregate) {
            $groupBy = isset($previous) ? $previous['column'] : $this->foreignKey;

            $sub = $this->newSubQuery($groupBy, $column, $aggregate);

            if (isset($previous)) {
                $this->addJoinSub($sub, $previous['sub'], $previous['column']);
            } elseif (isset($closure)) {
                $closure($sub);
            }

            if (array_key_last($columns) == $column) {
                $this->addJoinSub($this->query, $sub, $column);
            }

            $previous = [
                'sub'       => $sub,
                'column'    => $column
            ];
        }


        return $this;
    }

    /**
     * Get new grouped sub query for inner join clause.
     *
     * @param string $groupBy
     * @param string|null $column
     * @param string|null $aggregate
     * @return void
     */
    protected function newSubQuery($groupBy, $column = null, $aggregate = null)
    {
        $sub = $this->query->getModel()
            ->newQuery()
            ->groupBy($this->qualifyRelatedColumn($groupBy));

        if (!is_null($column)) {
            $sub->selectRaw($aggregate.'('.$column.') as '.$column.','.$this->foreignKey);
        }
            
        return $sub;
    }

    /**
     * Add join sub.
     *
     * @param Builder $parent
     * @param Builder $sub
     * @param string $on
     * @return void
     */
    protected function addJoinSub(Builder $parent, Builder $sub, $on)
    {
        $parent->joinSub($sub, $this->relationName, function ($join) use ($on) {
            $join
                ->on($this->qualifySubSelectColumn($on), '=', $this->qualifyRelatedColumn($on))
                ->on($this->qualifySubSelectColumn($this->foreignKey), '=', $this->qualifyRelatedColumn($this->foreignKey));
        });
    }

    /**
     * Guess the "hasOne" relationship name.
     *
     * @return string
     */
    protected function guessRelationship()
    {
        [$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    /**
     * Get the name of the relationship.
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }

    /**
     * Get related key name.
     *
     * @return string
     */
    protected function getRelatedKeyName()
    {
        return $this->getRelatedTableName().'.'.$this->query->getModel()->getKeyName();
    }

    /**
     * Get sub select alias.
     *
     * @return string
     */
    public function getSubSelectAlias()
    {
        return $this->getRelationName()."_{$this->localKey}";
    }

    /**
     * Determines wether the relationship is one-of-many.
     *
     * @return bool
     */
    public function isOneOfMany()
    {
        return $this->isOneOfMany;
    }

    /**
     * Get the subselect table alias.
     *
     * @return string
     */
    public function getSubSelectTableAlias()
    {
        return $this->getRelationName();
    }

    /**
     * Qualify related column.
     *
     * @param string $column
     * @return string
     */
    protected function qualifyRelatedColumn($column)
    {
        return Str::contains($column, '.') ? $column : $this->getRelatedTableName().".".$column;
    }

    /**
     * Get the qualified column name for the one-of-many subselect.
     *
     * @param  string $column
     * @return string
     */
    public function qualifySubSelectColumn($column)
    {
        $segments = explode('.', $column);

        return $this->getSubSelectTableAlias().'.'.end($segments);
    }

    /**
     * Get the related table name.
     *
     * @return string
     */
    protected function getRelatedTableName()
    {
        return $this->query->getModel()->getTable();
    }

    /**
     * Get the result query builder instance for the given query builder.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newOneOfManyQuery()
    {
        return $this->query->getModel()->newQuery();
    }
}
