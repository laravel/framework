<?php

namespace Illuminate\Tests\Foundation\Testing;

use RuntimeException;
use Illuminate\Foundation\Exceptions\Handler;

class ExceptionHandlerTest extends Handler
{
    public function register()
    {
        $this->ignore(RuntimeException::class, function (RuntimeException $exception) {
            return $exception->getCode() === 429;
        });
    }
}
