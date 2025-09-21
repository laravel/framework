<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @template TBaseClass of \Illuminate\Database\Eloquent\Casts\ArrayObject|\Illuminate\Support\Collection
 *
 * @extends \Illuminate\Contracts\Database\Eloquent\CastsAttributes<TBaseClass, iterable>
 */
class IterableCast implements CastsAttributes
{
    use Concerns\NormalizesArguments;

    /**
     * The base iterable class to cast
     *
     * @var class-string<TBaseClass>
     */
    public $class;

    /**
     * Should the resulting object instance not be cached.
     *
     * @var string|null
     */
    public $withoutObjectCaching;

    /**
     * Should encrypt the storable value in the database.
     *
     * @var string|null
     */
    public $encrypt;

    /**
     * A custom iterable class to hold the items.
     *
     * @var class-string<TBaseClass>|null
     */
    public $using;

    /**
     * The callback in "class@method" notation or class name to map items into.
     *
     * @var string|null
     */
    public $map;

    /**
     * Create a new Cast Iterable Attribute instance.
     *
     * @param  array{class-string<TBaseClass>, string|null, string|null, class-string<TBaseClass>|null, string|null}  $arguments
     */
    public function __construct(array $arguments)
    {
        [$this->class, $this->withoutObjectCaching, $this->encrypt, $this->using, $this->map] = $arguments;

        $this->normalize();
    }

    /**
     * @inheritDoc
     */
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        if (!isset($attributes[$key])) {
            return;
        }

        $data = $this->encrypt
            ? Json::decode(Crypt::decryptString($attributes[$key]))
            : Json::decode($attributes[$key]);

        if (!is_array($data)) {
            return;
        }

        $data = new Collection($data);

        if ($this->map) {
            $this->map = Str::parseCallback($this->map);

            $data = is_callable($this->map)
                ? $data->map($this->map)
                : $data->mapInto($this->map[0]);
        }

        return $this->makeIterableObject($data);
    }

    /**
     * Instances the target iterable class.
     *
     * @param  \Illuminate\Support\Collection $data
     * @return \Illuminate\Support\Collection|\Illuminate\Support\Fluent
     */
    protected function makeIterableObject($data)
    {
        return new ($this->using)($data->all());
    }

    /**
     * @inheritDoc
     */
    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if (! is_null($value)) {
            return [
                $key => $this->encrypt
                    ? Crypt::encryptString(Json::encode($value))
                    : Json::encode($value),
            ];
        }

        return null;
    }
}
