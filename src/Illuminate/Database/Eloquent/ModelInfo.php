<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use LogicException;

/**
 * @implements Arrayable<string, mixed>
 */
class ModelInfo implements Arrayable, ArrayAccess
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $class
     * @param  string  $database
     * @param  string  $table
     * @param  class-string|null  $policy
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $attributes
     * @param  \Illuminate\Support\Collection<int, array{name: string, type: string, related: class-string<\Illuminate\Database\Eloquent\Model>}>  $relations
     * @param  \Illuminate\Support\Collection<int, array{event: string, class: string}>  $events
     * @param  \Illuminate\Support\Collection<int, array{event: string, observer: array<int, string>}>  $observers
     * @param  class-string<\Illuminate\Database\Eloquent\Collection<\Illuminate\Database\Eloquent\Model>>  $collection
     * @param  class-string<\Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>>  $builder
     */
    public function __construct(
        public $class,
        public $database,
        public $table,
        public $policy,
        public $attributes,
        public $relations,
        public $events,
        public $observers,
        public $collection,
        public $builder,
    ) {
    }

    /**
     * Convert the data object back to array form.
     *
     * @return array{"class": class-string<\Illuminate\Database\Eloquent\Model>, database: string, table: string, policy: class-string|null, attributes: \Illuminate\Support\Collection<int, array<string, mixed>>, relations: \Illuminate\Support\Collection<int, array{name: string, type: string, related: class-string<\Illuminate\Database\Eloquent\Model>}>, events: \Illuminate\Support\Collection<int, array{event: string, class: string}>, observers: \Illuminate\Support\Collection<int, array{event: string, observer: array<int, string>}>, collection: class-string<\Illuminate\Database\Eloquent\Collection<\Illuminate\Database\Eloquent\Model>>, builder: class-string<\Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>>}
     */
    public function toArray()
    {
        return [
            'class' => $this->class,
            'database' => $this->database,
            'table' => $this->table,
            'policy' => $this->policy,
            'attributes' => $this->attributes,
            'relations' => $this->relations,
            'events' => $this->events,
            'observers' => $this->observers,
            'collection' => $this->collection,
            'builder' => $this->builder,
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) ? $this->{$offset} : false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return property_exists($this, $offset) ? $this->{$offset} : throw new \InvalidArgumentException("Property {$offset} does not exist.");
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException(self::class.' may not be mutated using array access.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException(self::class.' may not be mutated using array access.');
    }
}
