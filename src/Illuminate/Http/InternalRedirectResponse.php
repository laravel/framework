<?php

namespace Illuminate\Http;

class InternalRedirectResponse
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
