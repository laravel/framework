<?php

namespace Illuminate\Auth\Exceptions;

use Illuminate\Http\RedirectResponse;

class LoginRedirectException extends \RuntimeException
{

    public $redirect;

    public function __construct(RedirectResponse $redirect){
        $this->redirect = $redirect;
    }
}