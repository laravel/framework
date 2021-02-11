<?php

namespace Illuminate\Database\Eloquent\Relations;

class NullableMorphMany extends MorphMany
{
    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());

            if (! is_null($this->getParentKey())) {
                $this->query->whereNotNull($this->foreignKey);
            }

            $this->query->where($this->morphType, $this->morphClass);
        }
    }
}
