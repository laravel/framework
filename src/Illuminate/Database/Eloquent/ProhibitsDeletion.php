<?php

namespace Illuminate\Database\Eloquent;

use Exception;

/**
 * Trait ProhibitsDeletion
 *
 * Prevents deletion of a model instance when enabled. This applies to both
 * normal deletes and soft deletes (via SoftDeletes) because the `deleting`
 * model event is intercepted and the `delete()` method is overridden.
 *
 * By default, deletion is prohibited. You can toggle the behavior at runtime
 * via {@see shouldProhibitDeletion()}.
 */
trait ProhibitsDeletion
{
    /**
     * Whether deletion is currently prohibited for this model class.
     */
    protected static bool $prohibitDeletion = true;

    /**
     * Boot the ProhibitsDeletion trait for a model.
     *
     * Attaches a listener to the `deleting` model event to prevent deletion
     * when {@see shouldProhibitDeletion()} is true.
     *
     * @throws Exception If deletion is prohibited.
     */
    public static function bootProhibitsDeletion(): void
    {
        static::deleting(function (Model $model) {
            if ($model->shouldProhibitDeletion()) {
                $model->throwProhibited(false);
            }
        });
    }

    /**
     * Delete the model from the database.
     *
     * This method is overridden to enforce deletion prohibition when
     * {@see shouldProhibitDeletion()} is true.
     *
     * @return bool|null True if the model was deleted, false/null otherwise.
     *
     * @throws Exception If deletion is prohibited.
     */
    public function delete()
    {
        if ($this->shouldProhibitDeletion()) {
            $this->throwProhibited(false);
        }

        return parent::delete();
    }

    /**
     * Whether deletion should be prohibited for this model instance.
     * Override this in your model for custom logic.
     */
    protected function shouldProhibitDeletion(): bool
    {
        return static::$prohibitDeletion;
    }

    /**
     * Throw an exception indicating that deletion is prohibited.
     *
     * @throws Exception Always thrown to indicate deletion is prohibited.
     */
    protected function throwProhibited(bool $force): void
    {
        $class = static::class;
        $prefix = $force ? 'Force deletion' : 'Deletion';

        throw new Exception("$prefix of $class is prohibited.");
    }
}
