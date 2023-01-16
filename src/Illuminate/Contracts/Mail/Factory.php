<?php

namespace Illuminate\Contracts\Mail;

interface Factory
{
    /**
     * Get a mailer instance by name.
     *
     * @param  ?string  $name
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public function mailer($name = null);
}
