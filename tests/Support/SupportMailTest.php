<?php

namespace Illuminate\Tests\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\TestCase;

class SupportMailTest extends TestCase
{
    public function testItRegisterAndCallMacros()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testItRegisterAndCallMacrosWhenFaked()
    {
        Mail::macro('test', fn (string $str) => $str === 'foo'
            ? 'it works!'
            : 'it failed.',
        );

        Mail::fake();

        $this->assertEquals('it works!', Mail::test('foo'));
    }

    public function testEmailSent()
    {
        Mail::fake();
        Mail::assertNothingSent();

        Mail::to('hello@laravel.com')->send(new TestMail());

        Mail::assertSent(TestMail::class);
    }

    public function testNotificationEmailSent()
    {
        Mail::fake();
        Mail::assertNothingSent();

        $notification = new class extends Notification
        {
            public function via($notifiable)
            {
                return ['mail'];
            }

            public function toMail($notifiable)
            {
                return new TestMail();
            }
        };

        $notifiable = new class
        {
            use Notifiable;

            public $email = 'hello@laravel.com';
        };

        \Illuminate\Support\Facades\Notification::send($notifiable, $notification);

        Mail::assertSentCount(1);
    }
}

class TestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }
}
