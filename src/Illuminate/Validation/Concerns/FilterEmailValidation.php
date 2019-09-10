<?php

namespace Illuminate\Validation\Concerns;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Warning\Warning;

class FilterEmailValidation implements EmailValidation
{
    /**
     * Returns true if the given email is valid.
     *
     * @param  string  $email
     * @param  EmailLexer
     * @return bool
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Returns the validation error.
     *
     * @return InvalidEmail|null
     */
    public function getError()
    {
        //
    }

    /**
     * Returns the validation warnings.
     *
     * @return Warning[]
     */
    public function getWarnings()
    {
        return [];
    }
}
