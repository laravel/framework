<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;


trait HasCascadeSoftDeletes
{
    /**
     * Boot the has cascade soft deletes for a model.
     *
     * @return void
     */
    public static function bootHasCascadeSoftDeletes()
    {
        static::deleting(function (Model $model) {
            if (! $model->isForceDeleting()) {
                $model->cascadeSoftDelete();
            }
        });

        static::restored(function (Model $model) {
            $model->cascadeRestore();
        });
    }

    /**
     * Cascade soft delete to related models.
     *
     * @return void
     * 
     * @throws InvalidArgumentException
     */
    protected function cascadeSoftDelete()
    {
        $this->getCascadeRelations()->each(function (string $relation) {
            if (!method_exists($this, $relation)) {
                throw new InvalidArgumentException("Relation '{$relation}' does not exist on model " . get_class($this));
            }

            $query = $this->{$relation}();

            if (!$this->canCascadeDelete($query)) {
                return;
            }

            $query->chunkById(100, function ($related) {
                foreach ($related as $model) {
                    if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                        $model->delete();
                    }
                }
            });
        });
    }

    /**
     * Cascade restore to related models.
     *
     * @return void
     * 
     * @throws \InvalidArgumentException
     */
    protected function cascadeRestore()
    {
        $this->getCascadeRelations()->each(function (string $relation) {
            if (!method_exists($this, $relation)) {
                throw new InvalidArgumentException("Relation '{$relation}' does not exist on model " . get_class($this));
            }

            $query = $this->{$relation}()->withTrashed();

            if (!$this->canCascadeDelete($query)) {
                return;
            }

            $query->chunkById(100, function ($related) {
                foreach ($related as $model) {
                    if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                        $model->restore();
                    }
                }
            });
        });
    }

    /**
     * Get the relations that should be cascade soft deleted.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCascadeRelations()
    {
        return collect($this->cascadeSoftDeletes ?? []);
    }

    /**
     * Determine if the relation can be cascade deleted.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return bool
     */
    protected function canCascadeDelete(Relation $relation)
    {
        return $relation->getModel() instanceof Model &&
            in_array(SoftDeletes::class, class_uses_recursive($relation->getModel()));
    }
}