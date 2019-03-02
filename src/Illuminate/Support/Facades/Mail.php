<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\MailFake;

/**
 * @method static \Illuminate\Mail\PendingMail to($users)
 * @method static \Illuminate\Mail\PendingMail bcc($users)
 * @method static void raw(string $text, $callback)
 * @method static void send(string|array|\Illuminate\Contracts\Mail\Mailable $view, array $data = [], \Closure|string $callback = null)
 * @method static array failures()
 * @method static mixed queue(string|array|\Illuminate\Contracts\Mail\Mailable $view, string $queue = null)
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, string|array|\Illuminate\Contracts\Mail\Mailable $view, string $queue = null)
 * @method static void assertSent(string $mailable, \Closure|string $callback = null)
 * @method static void assertNotSent(string $mailable, \Closure|string $callback = null)
 * @method static void assertNothingSent()
 * @method static void assertQueued(string $mailable, \Closure|string $callback = null)
 * @method static void assertNotQueued(string $mailable, \Closure|string $callback = null)
 * @method static void assertNothingQueued()
 * @method static \Illuminate\Support\Collection sent(string $mailable, \Closure|string $callback = null)
 * @method static bool hasSent(string $mailable)
 * @method static \Illuminate\Support\Collection queued(string $mailable, \Closure|string $callback = null)
 * @method static bool hasQueued(string $mailable)
 *
 * @see \Illuminate\Mail\Mailer
 * @see \Illuminate\Support\Testing\Fakes\MailFake
 */
class Mail extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminate\Support\Testing\Fakes\MailFake
     */
    public static function fake()
    {
        static::swap($fake = new MailFake);

        return $fake;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mailer';
    }
}
