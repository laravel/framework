<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Testing\PendingFormRequest;

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
