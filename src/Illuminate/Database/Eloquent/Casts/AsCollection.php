<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AsCollection implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @template TCollection
     *
     * @param array{class-string<TCollection>,string} $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<TCollection<array-key, mixed>, iterable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            /**
             * @var class-string<TCollection>
             */
            protected string $collectionClass;
            protected bool $forceInstance;

            public function __construct(array $arguments)
            {
                $this->collectionClass = $arguments[0] ?? Collection::class;
                $this->forceInstance = ($arguments[1] ?? '') === 'force';

                if (! is_a($this->collectionClass, Collection::class, true)) {
                    throw new InvalidArgumentException('The provided class must extend ['.Collection::class.'].');
                }
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($attributes[$key])) {
                    return $this->defaultValue();
                }

                $data = Json::decode($attributes[$key]);

                if (! is_array($data)) {
                    return $this->defaultValue();
                }

                return new $this->collectionClass($data);
            }

            public function set($model, $key, $value, $attributes)
            {
                return [$key => Json::encode($value)];
            }

            /**
             * @return TCollection|null
             */
            protected function defaultValue(): ?Collection
            {
                return $this->forceInstance ? new $this->collectionClass : null;
            }
        };
    }

    /**
     * Specify the collection for the cast.
     *
     * @param  class-string<Collection>  $class
     * @param  bool $force
     * @return string
     */
    public static function using(string $class = Collection::class, bool $force = false): string
    {
        if ($force) {
            return static::class.':'.$class.',force';
        }

        return static::class.':'.$class;
    }

    /**
     * Always get a collection instance.
     *
     * @param  class-string<Collection>  $class
     * @return string
     */
    public static function force(string $class = Collection::class): string
    {
        return static::class.':'.$class.',force';
    }
}
