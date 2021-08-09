<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;

trait SerializesAndRestoresModelIdentifiers
{
    /**
     * Get the property value prepared for serialization.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getSerializedPropertyValue($value)
    {
        if ($value instanceof QueueableCollection) {
            $model = $value->first();

            return new ModelIdentifier(
                $value->getQueueableClass(),
                $value->getQueueableIds(),
                $model ? $this->getQueueableRelations($model) : [],
                $value->getQueueableConnection(),
                $model ? $this->getModelAttributes($model) : ["*"]
            );
        }

        if ($value instanceof QueueableEntity) {
            return new ModelIdentifier(
                get_class($value),
                $value->getQueueableId(),
                $this->getQueueableRelations($value),
                $value->getQueueableConnection(),
                $this->getModelAttributes($value)
            );
        }

        return $value;
    }

    /**
     * Get the restored property value after deserialization.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getRestoredPropertyValue($value)
    {
        if (! $value instanceof ModelIdentifier) {
            return $value;
        }

        return is_array($value->id)
                ? $this->restoreCollection($value)
                : $this->restoreModel($value);
    }

    /**
     * Restore a queueable collection instance.
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function restoreCollection($value)
    {
        if (! $value->class || count($value->id) === 0) {
            return new EloquentCollection;
        }

        $collection = $this->getQueryForModelRestoration(
            (new $value->class)->setConnection($value->connection), $value->id
        )->useWritePdo()->get($value->attributes);

        if (is_a($value->class, Pivot::class, true) ||
            in_array(AsPivot::class, class_uses($value->class))) {
            return $collection;
        }

        $collection = $collection->keyBy->getKey();

        $collectionClass = get_class($collection);

        return new $collectionClass(
            collect($value->id)->map(function ($id) use ($collection) {
                return $collection[$id] ?? null;
            })->filter()
        );
    }

    /**
     * Restore the model from the model identifier instance.
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function restoreModel($value)
    {
        return $this->getQueryForModelRestoration(
            (new $value->class)->setConnection($value->connection), $value->id
        )->useWritePdo()->firstOrFail($value->attributes)->load($value->relations ?? []);
    }

    /**
     * Get the query for model restoration.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array|int  $ids
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getQueryForModelRestoration($model, $ids)
    {
        return $model->newQueryForRestoration($ids);
    }

    /**
     * Get the model selected attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function getModelAttributes($model)
    {
        $attributes = array_keys($model->getAttributes());

        return !empty($attributes) ? $attributes : ["*"];
    }

    /**
     * Get the relation with it's selected attributes as string.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $relation
     * @return string
     */
    protected function getRelationAttributesString($model, $relation)
    {
        if (str_contains($relation, ".")) {
            return $relation;
        }

        $relationModel = $model->getRelationValue($relation)->first();

        $attributes = $this->getModelAttributes($relationModel);

        $relation = $relation . ":" . implode(",", $attributes);

        unset($attributes, $relationModel);

        return $relation;
    }

    /**
     * Get the queuebale relations.
     *
     * @param  \Illuminate\Database\Eloquent\Model $value
     * @return array
     */
    protected function getQueueableRelations($value)
    {
        $relations = [];

        foreach ($value->getQueueableRelations() as $relation) {
            $relations[] = $this->getRelationAttributesString($value, $relation);
        }

        return $relations;
    }
}
