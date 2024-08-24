<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Eloquent\Casts\EpochCast;

trait EpochSoftDeletes
{
    use SoftDeletes;

    /**
     * {@inheritdoc}
     */
    public static function bootSoftDeletes()
    {
        // it is important to override bootSoftDeletes method and leave it empty
        // to prevent the default scope of SoftDeletes trait from being applied
        // we use SoftDeletes trait, because we do not want to implement the whole logic from scratch
    }

    public static function bootEpochSoftDeletes(): void
    {
        static::addGlobalScope(new EpochSoftDeletingScope());
    }

    /**
     * {@inheritDoc}
     */
    public function initializeSoftDeletes(): void
    {
        if (! isset($this->casts[$this->getDeletedAtColumn()])) {
            $this->casts[$this->getDeletedAtColumn()] = EpochCast::class;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function runSoftDelete(): void
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp()->timestamp;

        $columns = [$this->getDeletedAtColumn() => $time];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->usesTimestamps() && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    /**
     * {@inheritDoc}
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            return tap($this->setKeysForSaveQuery($this->newModelQuery())->forceDelete(), function () {
                $this->exists = false;
            });
        }

        $this->runSoftDelete();
    }

    /**
     * {@inheritDoc}
     */
    public function restore()
    {
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = 0;

        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function trashed()
    {
        return $this->{$this->getDeletedAtColumn()} !== 0;
    }

    public function scopeWhereDeleted()
    {
        return $this->where($this->getDeletedAtColumn(), '!=', 0);
    }

    public function scopeWhereNotDeleted()
    {
        return $this->where($this->getDeletedAtColumn(), 0);
    }
}
