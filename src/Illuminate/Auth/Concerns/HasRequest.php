<?php

namespace Illuminate\Auth\Concerns;

use Symfony\Component\HttpFoundation\Request;

trait HasRequest
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Set the current request instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
