<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

class PreventRequestForgeryExceptStub extends PreventRequestForgery
{
    public function checkInExceptArray($request)
    {
        return $this->inExceptArray($request);
    }

    public function setExcept(array $except)
    {
        $this->except = $except;

        return $this;
    }
}
