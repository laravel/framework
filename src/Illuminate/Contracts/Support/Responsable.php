<?php

namespace Illuminate\Contracts\Support;

interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @return \Illuminate\Http\Response
     */
    public function toResponse();
}
