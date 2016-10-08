<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Closure;
use Illuminate\Foundation\Testing\Constraints\Mail;

trait InteractsWithMail
{
    public function getSwiftMessages()
    {
        $messages = $this->app->make('mailer')->getSwiftMailer()->getTransport()->getMessages();

        $this->assertNotEmpty($messages, 'No messages were sent');

        return $messages;
    }

    public function seeMail(Closure $assertions)
    {
        $constraints = new Mail($assertions);

        $this->assertNotNull(
            $this->findAnyMail($constraints),
            "Unable to match a mail that matches: {$constraints}."
        );

        return $this;
    }

    protected function findAnyMail($constraints)
    {
        return $this->getSwiftMessages()->first(function ($message) use ($constraints) {
            return $constraints->matches($message);
        });
    }
}
