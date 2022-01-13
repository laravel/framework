<?php

namespace Illuminate\Tests\Testing\Console\Fixtures;

use Illuminate\Http\RedirectResponse;

function signedRoute()
{
    return function () {
        return new RedirectResponse($this->urlGenerator->signedRoute('signed-route'));
    };
}

function accountId()
{
    return function ($account, $id) {
        //
    };
}
