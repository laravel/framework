<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface ComparesCastableAttributes
{
    /**
     * Compare two values for an attribute and check if they are equal.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value1
     * @param  mixed  $value2
     * @return bool
     */
    public function compare($model, string $key, $value1, $value2);
}
