<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Auth\Access\AuthorizationException;

class ShouldntReportAuthorizationExceptionTest
{
    public function __invoke(AuthorizationException $exception)
    {
        return true;
    }
}
