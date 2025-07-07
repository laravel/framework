<?php

namespace Illuminate\Contracts\Session;

use SessionHandlerInterface;

interface LaravelSessionHandlerInterface extends SessionHandlerInterface
{
    /**
     * Set the number of minutes the session should be valid.
     *
     * @param  int  $minutes
     * @return void
     */
    public function setMinutes(int $minutes);
}
