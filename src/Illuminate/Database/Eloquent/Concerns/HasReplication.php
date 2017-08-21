<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Arr;

trait HasReplication
{
    /**
     * The attributes that aren't replicable.
     *
     * @var array
     */
    protected $irreplicable = [];

    /**
     * Get the irreplicable attributes for the model.
     *
     * @return array
     */
    public function getIrreplicable()
    {
        return $this->irreplicable;
    }

    /**
     * Set the irreplicable attributes for the model.
     *
     * @param  array  $irreplicable
     * @return $this
     */
    public function irreplicable(array $irreplicable)
    {
        $this->irreplicable = $irreplicable;

        return $this;
    }

    /**
     * Get the irreplicable default attributes for the model.
     *
     * @return array
     */
    protected function getIrreplicableDefaults()
    {
        return [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];
    }

    /**
     * Determine if the given attribute may be replicated.
     *
     * @param  string  $key
     * @return bool
     */
    public function isReplicable($key)
    {
        $irreplicable = array_merge($this->irreplicable, $this->getIrreplicableDefaults());

        return ! in_array($key, $irreplicable);
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param  array|null  $except
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        $irreplicable = array_merge($this->irreplicable, $this->getIrreplicableDefaults());

        $attributes = Arr::except(
            $this->attributes, $except ? array_unique(array_merge($except, $irreplicable)) : $irreplicable
        );

        return tap(new static, function ($instance) use ($attributes) {
            $instance->setRawAttributes($attributes);

            $instance->setRelations($this->relations);
        });
    }
}
