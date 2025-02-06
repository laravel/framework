<?php

namespace Illuminate\Hashing;

use RuntimeException;

class BcryptValueTooLongException extends RuntimeException
{
    public function __construct(int $maxLength = BcryptHasher::BCRYPT_STR_LIMIT)
    {
        parent::__construct("Value is too long. Maximum length is {$maxLength} characters.");
    }
}
