<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait DeferRouteBinding
{
    protected ?object $deferredInit = null;

    protected ?bool $deferredInitResolved = null;

    public function resolveRouteBinding($value, $field = null)
    {
        $this->defer($value, $field, false);

        return $this;
    }

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        $this->defer($value, $field, true);

        return $this;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return $this->deferScoped($childType, $value, $field, false);
    }

    public function resolveSoftDeletableChildRouteBinding($childType, $value, $field)
    {
        return $this->deferScoped($childType, $value, $field, true);
    }

    public function __invoke(): static
    {
        if ($this->deferredInit !== null) {
            $deferredInit = $this->deferredInit;
            $this->deferredInit = null;
            parent::__construct();
            $model = $deferredInit();
            $this->exists = true;
            $this->setRawAttributes((array) $model->attributes, true);
            $this->setConnection($model->connection);
            $this->fireModelEvent('retrieved', false);
            $this->deferredInitResolved = true;
        }

        return $this;
    }

    public function __get($key)
    {
        $this();

        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        $this();

        parent::__set($key, $value);
    }

    public function __call($method, $parameters)
    {
        $this();

        return parent::__call($method, $parameters);
    }

    public function __isset($key)
    {
        $this();

        return parent::__isset($key);
    }

    public function __unset($key)
    {
        $this();

        parent::__unset($key);
    }

    public function __toString()
    {
        $this();

        return parent::__toString();
    }

    public function toArray()
    {
        $this();

        return parent::toArray();
    }

    public function toJson($options = 0)
    {
        $this();

        return parent::toJson($options);
    }

    public function jsonSerialize(): mixed
    {
        $this();

        return parent::jsonSerialize();
    }

    public function update(array $attributes = [], array $options = [])
    {
        $this();

        return parent::update($attributes, $options);
    }

    public function updateOrFail(array $attributes = [], array $options = [])
    {
        $this();

        return parent::updateOrFail($attributes, $options);
    }

    public function updateQuietly(array $attributes = [], array $options = [])
    {
        $this();

        return parent::updateQuietly($attributes, $options);
    }

    public function delete()
    {
        $this();

        return parent::delete();
    }

    public function deleteOrFail()
    {
        $this();

        return parent::deleteOrFail();
    }

    public function deleteQuietly()
    {
        $this();

        return parent::deleteQuietly();
    }

    public function save(array $options = [])
    {
        $this();

        return parent::save($options);
    }

    public function saveOrFail(array $options = [])
    {
        $this();

        return parent::saveOrFail($options);
    }

    public function saveQuietly(array $options = [])
    {
        $this();

        return parent::saveQuietly($options);
    }

    public function deferred(object $deferredInit): void
    {
        $this->deferredInitResolved = false;
        $this->deferredInit = $deferredInit;
    }

    protected function defer(mixed $value, ?string $field, bool $withTrashed): void
    {
        $this->deferredInitResolved = false;

        $closure = $withTrashed
            ? fn () => $this->resolveRouteBindingQuery($this, $value, $field)->withTrashed()->firstOrFail()
            : fn () => $this->resolveRouteBindingQuery($this, $value, $field)->firstOrFail();

        $this->deferredInit = new class('resolveRouteBindingQuery', $value, $field, $closure)
        {
            public function __construct(public string $method, public mixed $value, public ?string $field, public Closure $closure)
            {
            }

            public function __invoke()
            {
                return ($this->closure)();
            }
        };
    }

    protected function deferScoped(string $childType, mixed $value, ?string $field, bool $withTrashed): Model
    {
        /** @var Relation $relationship */
        $relationship = $this->{$this->childRouteBindingRelationshipName($childType)}();

        $child = $relationship->getModel();

        if (! isset(\trait_uses_recursive($child)[DeferRouteBinding::class])) {
            return parent::resolveChildRouteBindingQuery($childType, $value, $field)->first();
        }

        $closure = ! $withTrashed
            ? fn () => $this->resolveChildRouteBindingQuery($childType, $value, $field)->firstOrFail()
            : fn () => $this->resolveChildRouteBindingQuery($childType, $value, $field)->withTrashed()->firstOrFail();

        $deferredInit = new class('resolveChildRouteBindingQuery', $value, $field, $withTrashed, $closure)
        {
            public function __construct(public string $method, public mixed $value, public ?string $field, public bool $withTrashed, public Closure $closure)
            {
            }

            public function __invoke()
            {
                return ($this->closure)();
            }
        };

        $child->deferred($deferredInit);

        return $child;
    }
}
