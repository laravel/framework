<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface DeviatesCastableAttributes
{
    /**
     * Increment the attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function increment($model, string $key, $value, array $attributes);

    /**
     * Decrement the attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function decrement($model, string $key, $value, array $attributes);
}
