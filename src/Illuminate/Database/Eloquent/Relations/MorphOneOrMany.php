<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
     * The explicit polymorphic key type (e.g. 'string').
     *
     * @var string|null
     */
    protected $morphKeyType;

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
            $this->getRelationQuery()->where($this->morphType, $this->morphClass);

            parent::addConstraints();
        }
    }

    /** @inheritDoc */
    #[\Override]
    public function getParentKey()
    {
        $key = parent::getParentKey();

        return $this->morphForeignKeyIsString() && ! is_null($key)
            ? (string) $key
            : $key;
    }

    /** @inheritDoc */
    #[\Override]
    public function addEagerConstraints(array $models)
    {
        if ($this->morphForeignKeyIsString()) {
            $this->whereInEager(
                'whereIn',
                $this->foreignKey,
                array_map('strval', $this->getKeys($models, $this->localKey)),
                $this->getRelationQuery()
            );

            $this->getRelationQuery()->where($this->morphType, $this->morphClass);

            return;
        }

        parent::addEagerConstraints($models);

        $this->getRelationQuery()->where($this->morphType, $this->morphClass);
    }

    /** @inheritDoc */
    #[\Override]
    protected function whereInMethod(Model $model, $key)
    {
        if ($this->morphForeignKeyIsString()) {
            return 'whereIn';
        }

        return parent::whereInMethod($model, $key);
    }

    /**
     * Specify the polymorphic key type for the relationship.
     *
     * @param  string  $type
     * @return $this
     */
    public function morphKeyType(string $type)
    {
        $this->morphKeyType = $type;

        return $this;
    }

    /**
     * Determine if the polymorphic foreign key column should be treated as a string.
     *
     * @return bool
     */
    protected function morphForeignKeyIsString()
    {
        if ($this->morphKeyType === 'string') {
            return true;
        }

        try {
            return method_exists($this->related, 'hasCast')
                && $this->related->hasCast($this->getForeignKeyName(), ['string']);
        } catch (\Throwable) {
            return false;
        }
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
}
