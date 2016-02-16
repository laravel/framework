<?php

namespace Illuminate\Contracts\Http;

interface Response
{
    /**
     * Get the status code for the response.
     *
     * @return int
     */
    public function status();

    /**
     * Get the content of the response.
     *
     * @return string
     */
    public function content();

    /**
     * Get the headers of the response.
     *
     * @return \Symfony\Component\HttpFoundation\HeaderBag
     */
    public function headers();
}
