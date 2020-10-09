<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface ComparesCastableAttributes
{
    /**
     * Compare current and original attribute values.
     * Returns true if values are equal and false otherwise.
     *
     * @param  mixed  $value
     * @param  mixed  $originalValue
     * @return bool
     */
    public function compare($value, $originalValue);
}
