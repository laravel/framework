<?php

namespace Illuminate\Contracts\Debug;

interface ExceptionRenderer
{
    /**
     * Render the exception response.
     *
     * @return mixed
     */
    public function render();
}
