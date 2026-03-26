<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 * @template TResult
 *
 * @extends \Illuminate\Database\Eloquent\Relations\HasOneOrMany<TRelatedModel, TDeclaringModel, TResult>
 */
abstract class MorphOneOrMany extends HasOneOrMany
{
    /**
     * The foreign key type for the relationship.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The morph class of the parent model.
     *
     * @var class-string<TDeclaringModel>|string
     */
    protected $morphClass;

    /**
     * Create a new morph one or many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $localKey)
    {
        $this->morphType = $type;

        $this->morphClass = $parent->getMorphClass();

        parent::__construct($query, $parent, $id, $localKey);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->where($this->morphType, $this->morphClass);

            $query->where($this->foreignKey, '=', $this->castMorphKey($this->getParentKey()));

            $query->whereNotNull($this->foreignKey);
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $this->whereInEager(
            'whereIn',
            $this->foreignKey,
            $this->castMorphKeys($this->getKeys($models, $this->localKey)),
            $this->getRelationQuery()
        );

        $this->getRelationQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new instance of the related model. Allow mass-assignment.
     *
     * @param  array  $attributes
     * @return TRelatedModel
     */
    public function forceCreate(array $attributes = [])
    {
        $attributes[$this->getForeignKeyName()] = $this->getParentKey();
        $attributes[$this->getMorphType()] = $this->morphClass;

        return $this->applyInverseRelationToModel($this->related->forceCreate($attributes));
    }

    /**
     * Set the foreign ID and type for creating a related model.
     *
     * @param  TRelatedModel  $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model)
    {
        $model->{$this->getForeignKeyName()} = $this->getParentKey();

        $model->{$this->getMorphType()} = $this->morphClass;

        foreach ($this->getQuery()->pendingAttributes as $key => $value) {
            $attributes ??= $model->getAttributes();

            if (! array_key_exists($key, $attributes)) {
                $model->setAttribute($key, $value);
            }
        }

        $this->applyInverseRelationToModel($model);
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param  array  $values
     * @param  array|string  $uniqueBy
     * @param  array|null  $update
     * @return int
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        if (! empty($values) && ! is_array(array_first($values))) {
            $values = [$values];
        }

        foreach ($values as $key => $value) {
            $values[$key][$this->getMorphType()] = $this->getMorphClass();
        }

        return parent::upsert($values, $uniqueBy, $update);
    }

    /** @inheritDoc */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $query->qualifyColumn($this->getMorphType()), $this->morphClass
        );
    }

    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getQualifiedMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the plain morph type name without the table.
     *
     * @return string
     */
    public function getMorphType()
    {
        return last(explode('.', $this->morphType));
    }

    /**
     * Get the morph class of the parent model.
     *
     * @return class-string<TDeclaringModel>|string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }

    /**
     * Get the possible inverse relations for the parent model.
     *
     * @return array<non-empty-string>
     */
    protected function getPossibleInverseRelations(): array
    {
        return array_unique([
            Str::beforeLast($this->getMorphType(), '_type'),
            ...parent::getPossibleInverseRelations(),
        ]);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * Morph keys are cast to strings to ensure consistent matching
     * across databases with strict type comparison like PostgreSQL.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>  $results
     * @return array<array<array-key, TRelatedModel>>
     */
    protected function buildDictionary(EloquentCollection $results)
    {
        $foreign = $this->getForeignKeyName();

        $dictionary = [];

        $isAssociative = Arr::isAssoc($results->all());

        foreach ($results as $key => $item) {
            $pairKey = $this->castMorphKey($this->getDictionaryKey($item->{$foreign}));

            if ($pairKey === null) {
                continue;
            }

            if ($isAssociative) {
                $dictionary[$pairKey][$key] = $item;
            } else {
                $dictionary[$pairKey][] = $item;
            }
        }

        return $dictionary;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array<int, TDeclaringModel>  $models
     * @param  \Illuminate\Database\Eloquent\Collection<int, TRelatedModel>  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array<int, TDeclaringModel>
     */
    protected function matchOneOrMany(array $models, EloquentCollection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $this->castMorphKey($this->getDictionaryKey($model->getAttribute($this->localKey)));

            if ($key !== null && isset($dictionary[$key])) {
                $related = $this->getRelationValue($dictionary, $key, $type);

                $model->setRelation($relation, $related);

                $type === 'one'
                    ? $this->applyInverseRelationToModel($related, $model)
                    : $this->applyInverseRelationToCollection($related, $model);
            }
        }

        return $models;
    }

    /**
     * Cast the given morph key to a string.
     *
     * @param  string|int|null  $key
     * @return string|null
     */
    protected function castMorphKey($key)
    {
        return $key !== null ? (string) $key : null;
    }

    /**
     * Cast all morph keys in the given array to strings.
     *
     * @param  array  $keys
     * @return array
     */
    protected function castMorphKeys(array $keys)
    {
        return array_map(fn ($key) => $this->castMorphKey($key), $keys);
    }
}
