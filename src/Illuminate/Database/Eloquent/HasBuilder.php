<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Attributes\WithBuilder;

/**
 * @template TBuilder of \Illuminate\Database\Eloquent\Builder
 */
trait HasBuilder
{
    /**
     * Begin querying the model.
     *
     * @return TBuilder
     */
    public static function query()
    {
        return parent::query();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return TBuilder
     */
    public function newEloquentBuilder($query)
    {
        $builderClass = $this->getBuilderClass($query);

        return $builderClass ?? parent::newEloquentBuilder($query);
    }

    /**
     * Get builder from the UseBuilder attribute.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return TBuilder|null
     */
    protected static function getBuilderClass($query)
    {
        $attributes = (new \ReflectionClass(static::class))
            ->getAttributes(WithBuilder::class);

        if ($attributes !== []) {
            $withBuilder = $attributes[0]->newInstance();

            return new $withBuilder->builderClass($query);
        }

        return null;
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return TBuilder
     */
    public function newQuery()
    {
        return parent::newQuery();
    }

    /**
     * Get a new query builder that doesn't have any global scopes or eager loading.
     *
     * @return TBuilder
     */
    public function newModelQuery()
    {
        return parent::newModelQuery();
    }

    /**
     * Get a new query builder with no relationships loaded.
     *
     * @return TBuilder
     */
    public function newQueryWithoutRelationships()
    {
        return parent::newQueryWithoutRelationships();
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return TBuilder
     */
    public function newQueryWithoutScopes()
    {
        return parent::newQueryWithoutScopes();
    }

    /**
     * Get a new query instance without a given scope.
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return TBuilder
     */
    public function newQueryWithoutScope($scope)
    {
        return parent::newQueryWithoutScope($scope);
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
     *
     * @param  array|int  $ids
     * @return TBuilder
     */
    public function newQueryForRestoration($ids)
    {
        return parent::newQueryForRestoration($ids);
    }

    /**
     * Begin querying the model on a given connection.
     *
     * @param  string|null  $connection
     * @return TBuilder
     */
    public static function on($connection = null)
    {
        return parent::on($connection);
    }

    /**
     * Begin querying the model on the write connection.
     *
     * @return TBuilder
     */
    public static function onWriteConnection()
    {
        return parent::onWriteConnection();
    }

    /**
     * Begin querying a model with eager loading.
     *
     * @param  array|string  $relations
     * @return TBuilder
     */
    public static function with($relations)
    {
        return parent::with($relations);
    }
}
