<?php

namespace Illuminate\Hashing;

use RuntimeException;

class BcryptValueTooLongException extends RuntimeException
{
    public function __construct(int $maxLength = BcryptHasher::MAX_BYTE_LENGTH)
    {
        parent::__construct("Value to hash is too long. Bcrypt only allows for a maximum length of {$maxLength} bytes. Shorten your value or use a different hashing algorithm.");
    }
}
