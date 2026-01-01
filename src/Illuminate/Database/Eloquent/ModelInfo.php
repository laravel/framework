<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use LogicException;

/**
 * @implements Arrayable<string, mixed>
 */
class ModelInfo implements Arrayable, ArrayAccess
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $class  The model's fully-qualified class.
     * @param  string  $database  The database connection name.
     * @param  string  $table  The database table name.
     * @param  class-string|null  $policy  The policy that applies to the model.
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $attributes  The attributes available on the model.
     * @param  \Illuminate\Support\Collection<int, array{name: string, type: string, related: class-string<\Illuminate\Database\Eloquent\Model>}>  $relations  The relations defined on the model.
     * @param  \Illuminate\Support\Collection<int, array{event: string, class: string}>  $events  The events that the model dispatches.
     * @param  \Illuminate\Support\Collection<int, array{event: string, observer: array<int, string>}>  $observers  The observers registered for the model.
     * @param  class-string<\Illuminate\Database\Eloquent\Collection<TModel>>  $collection  The Collection class that collects the models.
     * @param  class-string<\Illuminate\Database\Eloquent\Builder<TModel>>  $builder  The Builder class registered for the model.
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
     * Convert the model info to an array.
     *
     * @return array{
     *     "class": class-string<\Illuminate\Database\Eloquent\Model>,
     *     database: string,
     *     table: string,
     *     policy: class-string|null,
     *     attributes: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     relations: \Illuminate\Support\Collection<int, array{name: string, type: string, related: class-string<\Illuminate\Database\Eloquent\Model>}>,
     *     events: \Illuminate\Support\Collection<int, array{event: string, class: string}>,
     *     observers: \Illuminate\Support\Collection<int, array{event: string, observer: array<int, string>}>, collection: class-string<\Illuminate\Database\Eloquent\Collection<\Illuminate\Database\Eloquent\Model>>,
     *     builder: class-string<\Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>>
     * }
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
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return property_exists($this, $offset) ? $this->{$offset} : throw new InvalidArgumentException("Property {$offset} does not exist.");
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
