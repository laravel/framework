<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;
use Visus\Cuid2\Cuid2;

trait HasCuid
{
    use HasUniqueStringIds;

    /**
     * Generate a new unique key for the model.
     */
    public function newUniqueId(): string
    {
        $size = intval(config('cuid.length', 24));
        $cuid = new Cuid2(maxLength: ($size < 2 || $size > 32) ? 24 : $size);

        return $cuid->toString();
    }

    /**
     * Determine if given key is valid.
     *
     * @param  mixed  $value
     */
    protected function isValidUniqueId($value): bool
    {
        return Str::isCuid($value);
    }
}
