<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Testing\PendingFormRequest;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Testing\PendingCommand;

trait InteractsWithFormRequest
{
    /**
     * Call artisan command and return code.
     *
     * @param \Illuminate\Foundation\Http\FormRequest|string $formRequest
     * @param string $route
     * @param string $method
     * @return \Illuminate\Testing\PendingFormRequest
     */
    protected function formRequest($formRequest, $route, $method) {
        return new PendingFormRequest($this, $this->app, $formRequest, $route, $method);
    }
}
