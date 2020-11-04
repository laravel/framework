<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Exceptions\Handler;
use LogicException;
use RuntimeException;

class BackwardsCompatibleExceptionHandlerTest extends Handler
{
    protected $dontReport = [
        RuntimeException::class,
        LogicException::class => false,
    ];
}
