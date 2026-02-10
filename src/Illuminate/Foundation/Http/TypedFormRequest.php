<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\SelfBuilding;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

abstract class TypedFormRequest implements SelfBuilding
{
    /**
     * Build a new TypedFormRequest instance.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function newInstance(): static
    {
        return Container::getInstance()
            ->make(TypedFormRequestFactory::class, ['requestClass' => static::class])
            ->build();
    }

    /**
     * Create a TypedFormRequest without authorization.
     *
     * @param  array|\Illuminate\Http\Request  $input
     * @return static
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function from($input): static
    {
        if (! $input instanceof Request) {
            $input = $input instanceof Arrayable ? $input->toArray() : $input;

            $input = Request::create('', parameters: $input);
        }

        return Container::getInstance()
            ->make(TypedFormRequestFactory::class, ['requestClass' => static::class, 'request' => $input])
            ->withAuthorization(false)
            ->build();
    }
}
