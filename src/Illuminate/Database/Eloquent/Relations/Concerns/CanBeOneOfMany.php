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
     * @param  string|null $ofMany
     * @return $this
     */
    public function ofMany(Closure $closure = null)
    {
        $this->isOneOfMany = true;

        $this->relationName = $this->guessRelationship();

        $sub = $this->query->getModel()->newQuery()
            ->groupBy($this->foreignKey);

        if ($closure instanceof Closure) {
            $closure($sub);
        }

        $this->query->joinSub($sub, $this->relationName, function ($join) {
            $key = $this->query->getModel()->getKeyName();
            $join->on($this->qualifySubSelectColumn($key), '=', $this->query->getModel()->getTable() . '.'.$key);
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
