<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ReflectionNamedType;
use ReflectionParameter;

#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParameter implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public string $parameter, public mixed $default = null)
    {
    }

    /**
     * Resolve the query parameter.
     *
     * @throws BindingResolutionException
     */
    public static function resolve(self $attribute, Container $container, ?ReflectionParameter $parameter = null): mixed
    {
        $value = $container->make('request')->query($attribute->parameter, $attribute->default);

        // If no reflection parameter provided, return raw value
        if (! $parameter) {
            return $value;
        }

        // Check if value is empty and nullable
        if (self::isEmptyValue($value)) {
            return self::getValueIfEmpty($parameter);
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $className = $type->getName();

            if (is_subclass_of($className, UrlRoutable::class)) {
                $model = $container->make($className)->resolveRouteBinding($value);

                if (! $model) {
                    throw (new ModelNotFoundException)->setModel($className, [$value]);
                }

                return $model;
            }
        }

        return $value;
    }

    private static function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * @throws BindingResolutionException
     */
    private static function getValueIfEmpty(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new BindingResolutionException;
    }
}
