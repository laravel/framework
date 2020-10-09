<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface ComparesCastableAttributes
{
    /**
     * Compare current and original attribute values.
     *
     * @param  mixed  $value
     * @param  mixed  $originalValue
     * @return mixed
     */
    public function compare($value, $originalValue);
}
