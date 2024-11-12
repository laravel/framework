<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpParser\Node\Expr\AssignOp\Mod;

class FrozenModelException extends \RuntimeException
{
    /**
     * @param  Model  $model
     * @return self
     */
    public static function forFill(Model $model)
    {
        return new self(sprintf("Cannot fill properties on Model [%s] because it is frozen.", $model::class));
    }

    /**
     * @param  Model  $model
     * @param  string  $attribute
     * @return self
     */
    public static function forSetAttribute(Model $model, string $attribute)
    {
        return new self(sprintf("Cannot set property [%s] on Model [%s] because it is frozen.", $attribute,
            $model::class));
    }

    public static function forRelations(Model $model, array $relations)
    {
        return new self(
            sprintf(
                "Cannot load relation(s) [%s] on Model [%s] because it is frozen.",
                Arr::join($relations, ', '),
                $model::class
            )
        );
    }

    public static function forUnset(Model $model)
    {
        return new self(sprintf("Cannot unset properties or relations on Model [%s] because it is frozen.", $model::class));
    }
}
