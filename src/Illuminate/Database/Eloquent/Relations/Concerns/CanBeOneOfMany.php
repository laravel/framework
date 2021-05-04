<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

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
     * The one-of-many parent query builder.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $oneOfManyQuery;

    /**
     * The methods that should be forwarded to the one-of-many query builder
     * instance.
     *
     * @var array
     */
    protected $forwardToOneOfManyQuery = [
        'get', 'exists', 'count'
    ];

    /**
     * Wether the relation is a partial of a one-to-many relationship.
     *
     * @param  boolean $ofMany
     * @return $this
     */
    public function ofMany(bool $ofMany = true)
    {
        $this->isOneOfMany = true;

        if ($ofMany) {
            $this->setOneOfManyQuery();
        }

        return $this;
    }

    /**
     * Initially set one-of-many parent query.
     *
     * @return void
     */
    protected function setOneOfManyQuery()
    {
        if ($this->oneOfManyQuery) {
            return;
        }

        $this->oneOfManyQuery = $this->getOneOfManyQueryFor($this->query);
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
    protected function getSubSelectAlias()
    {
        return $this->getRelatedTableName()."_{$this->localKey}_".spl_object_id($this);
    }

    /**
     * Determines wether the relationship is one-of-many.
     *
     * @return boolean
     */
    public function isOneOfMany()
    {
        return $this->isOneOfMany;
    }

    /**
     * Add subselect contstraints to the given query builder.
     *
     * @param  Builder $query
     * @return void
     */
    protected function addSubSelectConstraintsTo(Builder $query)
    {
        $query
            ->from($this->getRelatedTableName(), $this->getSubSelectTableAlias())
            ->whereColumn($this->qualifySubSelectColumn($this->foreignKey), $this->foreignKey)
            ->select($this->qualifySubSelectColumn($query->getModel()->getKeyName()))
            ->take(1);
    }

    /**
     * Get the subselect table alias.
     *
     * @return string
     */
    protected function getSubSelectTableAlias()
    {
        return $this->getRelatedTableName().'_'.spl_object_id($this);
    }

    /**
     * Get the qualified subselect column name.
     *
     * @param  string $column
     * @return string
     */
    protected function qualifySubSelectColumn($column)
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
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getOneOfManyQueryFor(Builder $query)
    {
        return $query->clone();
    }

    /**
     * Resolve the one-of-many query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function resolveOneOfManyQuery(Builder $query = null)
    {
        if (is_null($query)) {
            $query = $this->query;
        }

        $this->addSubSelectConstraintsTo($query);

        return $this->oneOfManyQuery
            ->whereExists(function ($existsQuery) use ($query) {
                $existsQuery
                    ->selectSub($query, $this->getSubSelectAlias())
                    ->whereColumn($this->getSubSelectAlias(), $this->getRelatedKeyName());
            });
    }

    /**
     * Determines wether the given query method should be forwarded to the
     * one-of-many query.
     *
     * @param string $method
     * @return bool
     */
    protected function shouldForwardedToOneOfManyQuery($method)
    {
        return $this->isOneOfMany()
            && in_array($method, $this->forwardToOneOfManyQuery);
    }

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        $query = $this->query;
        if($this->shouldForwardedToOneOfManyQuery($method)) {
            $query = $this->resolveOneOfManyQuery();
        }

        $result = $this->forwardCallTo($query, $method, $parameters);

        if ($result === $query) {
            return $this;
        }

        return $result;
    }
}
