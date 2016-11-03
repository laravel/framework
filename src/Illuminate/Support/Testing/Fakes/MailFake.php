<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable;
use PHPUnit_Framework_Assert as PHPUnit;

class MailFake implements Mailer
{
    /**
     * All of the mailables that have been sent.
     *
     * @var array
     */
    protected $mailables = [];

    /**
     * Assert if a mailable was sent based on a truth-test callback.
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertSent($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->sent($mailable, $callback)->count() > 0,
            "The expected [{$mailable}] mailable was not sent."
        );
    }

    /**
     * Assert if a mailable was sent based on a truth-test callback.
     *
     * @param  mixed  $users
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertSentTo($users, $mailable, $callback = null)
    {
        $users = $this->formatRecipients($users);

        return $this->assertSent($mailable, function ($mailable, $to) use ($users, $callback) {
            if (! $this->recipientsMatch($users, $this->formatRecipients($to))) {
                return false;
            }

            if (! is_null($callback)) {
                return $callback(...func_get_args());
            }

            return true;
        });
    }

    /**
     * Format the recipients into a collection.
     *
     * @param  mixed  $recipients
     * @return \Illuminate\Support\Collection
     */
    protected function formatRecipients($recipients)
    {
        if ($recipients instanceof Collection) {
            return $recipients;
        }

        return collect(is_array($recipients) ? $recipients : [$recipients]);
    }

    /**
     * Determine if two given recipient lists match.
     *
     * @param  \Illuminate\Support\Collection  $expected
     * @param  \Illuminate\Support\Collection  $recipients
     * @return bool
     */
    protected function recipientsMatch($expected, $recipients)
    {
        $expected = $expected->map(function ($expected) {
            return is_object($expected) ? $expected->email : $expected;
        });

        return $recipients->map(function ($recipient) {
            if (is_array($recipient)) {
                return $recipient['email'];
            }

            return is_object($recipient) ? $recipient->email : $recipient;
        })->diff($expected)->count() === 0;
    }

    /**
     * Determine if a mailable was sent based on a truth-test callback.
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotSent($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->sent($mailable, $callback)->count() === 0,
            "The unexpected [{$mailable}] mailable was sent."
        );
    }

    /**
     * Get all of the mailables matching a truth-test callback.
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function sent($mailable, $callback = null)
    {
        if (! $this->hasSent($mailable)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return $this->mailablesOf($mailable)->filter(function ($mailable) use ($callback) {
            return $callback($mailable->mailable, ...array_values($mailable->getRecipients()));
        });
    }

    /**
     * Determine if the given mailable has been sent.
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasSent($mailable)
    {
        return $this->mailablesOf($mailable)->count() > 0;
    }

    /**
     * Get all of the mailed mailables for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function mailablesOf($type)
    {
        return collect($this->mailables)->filter(function ($m) use ($type) {
            return $m->mailable instanceof $type;
        });
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return MailableMailer
     */
    public function to($users)
    {
        $this->mailables[] = $mailable = (new MailableFake)->to($users);

        return $mailable;
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return MailableMailer
     */
    public function bcc($users)
    {
        $this->mailables[] = $mailable = (new MailableFake)->bcc($users);

        return $mailable;
    }

    /**
     * Send a new message when only a raw text part.
     *
     * @param  string  $text
     * @param  \Closure|string  $callback
     * @return int
     */
    public function raw($text, $callback)
    {
        //
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if (! $view instanceof Mailable) {
            return;
        }

        $view->build();

        $mailable = new MailableFake;

        $mailable->mailable = $view;

        if ($recipients = $view->getTo()) {
            $mailable->to($recipients);
        }

        if ($recipients = $view->getBcc()) {
            $mailable->bcc($recipients);
        }

        if ($recipients = $view->getCc()) {
            $mailable->cc($recipients);
        }

        $this->mailables[] = $mailable;
    }

    /**
     * Get the array of failed recipients.
     *
     * @return array
     */
    public function failures()
    {
        //
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, array $data = [], $callback = null, $queue = null)
    {
        $this->send($view);
    }
}
