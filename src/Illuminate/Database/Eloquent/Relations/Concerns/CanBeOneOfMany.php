<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\Eloquent\Relations\Relation;

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
     * @param  Closure|string|null $column
     * @param  string|null $relation
     * @return $this
     */
    public function ofMany($column = null, $aggregate = 'MAX', $relation = null)
    {
        $this->isOneOfMany = true;

        if (is_null($this->relationName = $relation)) {
            $this->relationName = $this->guessRelationship();
        }

        $sub = $this->query->getModel()->newQuery()
            ->groupBy($this->foreignKey);

        $keyName = $this->query->getModel()->getKeyName();

        if ($column instanceof Closure) {
            $column($sub);
        } else {
            $sub->selectRaw(
                $aggregate.'('.$column.')' . $column == $keyName ? " as {$column}" : ", {$keyName}"
            );
        }

        $this->query->joinSub($sub, $this->relationName, function ($join) use ($keyName) {
            $join->on($this->qualifySubSelectColumn($keyName), '=', $this->query->getModel()->getTable() . '.'.$keyName);
        });

        return $this;
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
