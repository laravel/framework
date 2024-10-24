<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

trait HasUlids
{
    use HasUniqueStringIds;

    /**
     * Generate a new ULID for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return strtolower((string) Str::ulid());
    }

    /**
     * Determine if value is a valid ULID.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidKey($value): bool
    {
        return Str::isUlid($value);
    }
}
