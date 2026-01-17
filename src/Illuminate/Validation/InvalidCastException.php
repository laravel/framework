<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\CastsValue;
use RuntimeException;
use Throwable;

class InvalidCastException extends RuntimeException
{
    /**
     * The attribute key that caused the exception.
     *
     * @var string|null
     */
    public $key;

    /**
     * The cast type that caused the exception.
     *
     * @var string|CastsValue|CastsAttributes|null
     */
    public $castType;

    /**
     * Create a new invalid cast exception instance.
     *
     * @param  string  $message
     * @param  string|null  $key
     * @param  string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|null  $castType
     * @param  \Throwable|null  $previous
     */
    public function __construct(
        string $message,
        ?string $key = null,
        string|CastsValue|CastsAttributes|null $castType = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->key = $key;
        $this->castType = $castType;
    }
}
