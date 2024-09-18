<?php

namespace Illuminate\Support\Exceptions;

use Illuminate\Support\Recursable;
use RuntimeException;

class RecursableNotFoundException extends RuntimeException
{
    public static function make(Recursable $recursable)
    {
        return new self(sprintf(
            'Recursable value cannot be found for [%s].',
            $recursable->signature ?: implode('@', [
                $recursable->object ? get_class($recursable->object) : 'global',
                $recursable->hash,
            ]),
        ));
    }
}
