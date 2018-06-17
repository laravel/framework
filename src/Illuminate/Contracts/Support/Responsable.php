<?php

namespace Illuminate\Contracts\Support;

interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Support\Responsable
     */
    public function toResponse($request);
}
