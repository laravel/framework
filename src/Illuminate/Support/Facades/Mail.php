<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\MailFake;

/**
 * @method static \Illuminate\Mail\Mailer mailer(string|null $name = null)
 * @method static \Illuminate\Mail\PendingMail bcc($users)
 * @method static \Illuminate\Mail\PendingMail to($users)
 * @method static \Illuminate\Support\Collection queued(string $mailable, \Closure|string $callback = null)
 * @method static \Illuminate\Support\Collection sent(string $mailable, \Closure|string $callback = null)
 * @method static array failures()
 * @method static bool hasQueued(string $mailable)
 * @method static bool hasSent(string $mailable)
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, \Illuminate\Contracts\Mail\Mailable|string|array $view, string $queue = null)
 * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, \Illuminate\Contracts\Mail\Mailable|string|array $view)
 * @method static mixed queue(\Illuminate\Contracts\Mail\Mailable|string|array $view, string $queue = null)
 * @method static mixed queueOn(string $queue, \Illuminate\Contracts\Mail\Mailable|string|array $view)
 * @method static void assertNotQueued(string $mailable, callable $callback = null)
 * @method static void assertNotSent(string $mailable, callable|int $callback = null)
 * @method static void assertNothingQueued()
 * @method static void assertNothingSent()
 * @method static void assertQueued(string $mailable, callable|int $callback = null)
 * @method static void assertSent(string $mailable, callable|int $callback = null)
 * @method static void raw(string $text, $callback)
 * @method static void plain(string $view, array $data, $callback)
 * @method static void html(string $html, $callback)
 * @method static void send(\Illuminate\Contracts\Mail\Mailable|string|array $view, array $data = [], \Closure|string $callback = null)
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
        return 'mail.manager';
    }
}
