<?php

declare(strict_types=1);

namespace Illuminate\Database\Eloquent;

class IdentityManager
{
    /**
     * @var \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Model>
     */
    private $models;

    /**
     * Create a new identity map instance.
     *
     * @param \Illuminate\Database\Eloquent\Collection|null $collection
     */
    public function __construct(\Illuminate\Support\Collection $collection = null)
    {
        $this->models = $collection ?? new \Illuminate\Support\Collection;
    }

    /**
     * Get the model identifier from a model or return the provided string.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $identifier
     *
     * @throws \Illuminate\Database\Eloquent\ModelIdentityException
     *
     * @return string
     */
    private function getIdentifier(Model|string $identifier)
    {
        return $identifier instanceof Model ? $identifier->getModelIdentifier() : $identifier;
    }

    /**
     * Determine whether a model is stored for an identifier.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $identifier
     *
     * @return bool
     */
    public function hasModel(Model|string $identifier)
    {
        return $this->models->has($this->getIdentifier($identifier));
    }

    /**
     * Get a model for the given identifier.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $identifier
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getModel(Model|string $identifier)
    {
        return $this->models->get($identifier);
    }

    /**
     * Store the provided model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return static
     */
    public function storeModel(Model $model)
    {
        $this->models->put($this->getIdentifier($model), $model);

        return $this;
    }

    /**
     * Forget the provided model.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $identifier
     *
     * @return static
     */
    public function forgetModel(Model|string $identifier)
    {
        if ($this->hasModel($identifier)) {
            $this->models->forget($this->getIdentifier($identifier));
        }

        return $this;
    }

    /**
     * Flush all models from the identity map.
     *
     * @return static
     */
    public function flushModels()
    {
        $this->models = new \Illuminate\Support\Collection;

        return $this;
    }
}
