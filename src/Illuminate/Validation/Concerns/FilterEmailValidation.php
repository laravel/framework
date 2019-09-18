<?php

namespace Illuminate\Validation\Concerns;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\EmailValidation;

class FilterEmailValidation implements EmailValidation
{
    /**
     * Returns true if the given email is valid.
     *
     * @param  string  $email
     * @param  \Egulias\EmailValidator\EmailLexer  $emailLexer
     * @return bool
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Returns the validation error.
     *
     * @return \Egulias\EmailValidator\Exception\InvalidEmail|null
     */
    public function getError()
    {
        //
    }

    /**
     * Returns the validation warnings.
     *
     * @return \Egulias\EmailValidator\Warning\Warning[]
     */
    public function getWarnings()
    {
        return [];
    }
}
