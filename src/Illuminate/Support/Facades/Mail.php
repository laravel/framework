<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\MailFake;

/**
 * @method static void alwaysFrom(string $address, string | null $name) Set the global from address and name.
 * @method static void alwaysReplyTo(string $address, string | null $name) Set the global reply-to address and name.
 * @method static void alwaysTo(string $address, string | null $name) Set the global to address and name.
 * @method static \Illuminate\Mail\PendingMail to(mixed $users) Begin the process of mailing a mailable class instance.
 * @method static \Illuminate\Mail\PendingMail bcc(mixed $users) Begin the process of mailing a mailable class instance.
 * @method static void raw(string $text, mixed $callback) Send a new message when only a raw text part.
 * @method static void plain(string $view, array $data, mixed $callback) Send a new message when only a plain part.
 * @method static \Illuminate\View\View render(string | array $view, array $data) Render the given message as a view.
 * @method static void send(string | array | \Illuminate\Mail\MailableContract $view, array $data, \Closure | string $callback) Send a new message using a view.
 * @method static mixed queue(string | array | \Illuminate\Mail\MailableContract $view, string | null $queue) Queue a new e-mail message for sending.
 * @method static mixed onQueue(string $queue, string | array $view) Queue a new e-mail message for sending on the given queue.
 * @method static mixed queueOn(string $queue, string | array $view) Queue a new e-mail message for sending on the given queue.
 * @method static mixed later(\DateTimeInterface | \DateInterval | int $delay, string | array | \Illuminate\Mail\MailableContract $view, string | null $queue) Queue a new e-mail message for sending after (n) seconds.
 * @method static mixed laterOn(string $queue, \DateTimeInterface | \DateInterval | int $delay, string | array $view) Queue a new e-mail message for sending after (n) seconds on the given queue.
 * @method static \Illuminate\Contracts\View\Factory getViewFactory() Get the view factory instance.
 * @method static \Swift_Mailer getSwiftMailer() Get the Swift Mailer instance.
 * @method static array failures() Get the array of failed recipients.
 * @method static void setSwiftMailer(\Swift_Mailer $swift) Set the Swift Mailer instance.
 * @method static $this setQueue(\Illuminate\Contracts\Queue\Factory $queue) Set the queue manager instance.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
 *
 * @see \Illuminate\Mail\Mailer
 */
class Mail extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new MailFake);
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
