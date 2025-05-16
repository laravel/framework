<?php

namespace Illuminate\Mail;

use Exception;

class MissingSubjectException extends Exception
{
    /**
     * Create a new missing attribute exception instance.
     *
     * @param  Mailable  $mailable
     * @return void
     */
    public function __construct(Mailable $mailable)
    {
        parent::__construct(sprintf(
            'The mailable class [%s] is missing a defined subject.',
            $mailable::class
        ));
    }
}
