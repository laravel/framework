<?php

namespace Illuminate\Support\Testing\Fakes;

use InvalidArgumentException;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\MailableContract;
use PHPUnit\Framework\Assert as PHPUnit;

class MailFake implements Mailer
{
    /**
     * All of the mailables that have been sent.
     *
     * @var array
     */
    protected $mailables = [];

    /**
     * All of the mailables that have been queued;
     * @var array
     */
    protected $queuedMailables = [];

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
     * Determine if a mailable was not sent based on a truth-test callback.
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
     * Assert that no mailables were sent.
     *
     * @return void
     */
    public function assertNothingSent()
    {
        PHPUnit::assertEmpty($this->mailables, 'Mailables were sent unexpectedly.');
    }

    /**
     * Assert if a mailable was queued based on a truth-test callback.
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertQueued($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->queued($mailable, $callback)->count() > 0,
            "The expected [{$mailable}] mailable was not queued."
        );
    }

    /**
     * Determine if a mailable was not queued based on a truth-test callback.
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotSent($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            $this->queued($mailable, $callback)->count() === 0,
            "The unexpected [{$mailable}] mailable was queued."
        );
    }

    /**
     * Assert that no mailables were queued.
     *
     * @return void
     */
    public function assertNothingQueued()
    {
        PHPUnit::assertEmpty($this->mailables, 'Mailables were queued unexpectedly.');
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
            return $callback($mailable);
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
     * Get all of the queued mailables matching a truth-test callback.
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function queued($mailable, $callback = null)
    {
        if (! $this->hasQueued($mailable)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return $this->mailablesOf($mailable, true)->filter(function ($mailable) use ($callback) {
            return $callback($mailable);
        });
    }

    /**
     * Determine if the given mailable has been queued.
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasQueued($mailable)
    {
        return $this->mailablesOf($mailable, true)->count() > 0;
    }

    /**
     * Get all of the mailed mailables for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function mailablesOf($type, $queued = false)
    {
        $mailables = $queued ? $this->queuedMailables : $this->mailables;

        return collect($mailables)->filter(function ($mailable) use ($type) {
            return $mailable instanceof $type;
        });
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users)
    {
        return (new PendingMailFake($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users)
    {
        return (new PendingMailFake($this))->bcc($users);
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

        $this->mailables[] = $view;
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
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        $this->queuedMailables[] = $view;
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
}
