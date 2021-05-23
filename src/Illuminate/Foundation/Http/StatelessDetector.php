<?php

namespace Illuminate\Foundation\Http;

class StatelessDetector
{
    /**
     * Detects whether the request is stateless.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function isStateless($request)
    {
        return in_array($request->getHost(), config('app.stateless_domains', []), true);
    }
}
