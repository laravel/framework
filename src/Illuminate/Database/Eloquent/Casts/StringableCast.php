<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @template TBaseClass
 *
 * @extends \Illuminate\Contracts\Database\Eloquent\CastsAttributes<TBaseClass, \Stringable|string>
 */
class StringableCast implements CastsAttributes
{
    use Concerns\NormalizesArguments;

    /**
     * The base iterable class to cast.
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
     * A custom iterable class to hold the item.
     *
     * @var class-string<TBaseClass>|null
     */
    public $using;

    /**
     * Create a new Cast Iterable Attribute instance.
     *
     * @param  array{class-string<TBaseClass>, string|null, string|null, class-string<TBaseClass>|null}  $arguments
     */
    public function __construct(array $arguments)
    {
        [$this->class, $this->withoutObjectCaching, $this->encrypt, $this->using] = $arguments;

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

        return new ($this->using)($this->encrypt
            ? Crypt::decryptString($attributes[$key])
            : $attributes[$key]);
    }

    /**
     * @inheritDoc
     */
    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if (!is_null($value)) {
            return [
                $key => $this->encrypt
                    ? Crypt::encryptString($value)
                    : $value,
            ];
        }

        return null;
    }
}
