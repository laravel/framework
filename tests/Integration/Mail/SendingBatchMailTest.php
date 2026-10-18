<?php

namespace Illuminate\Tests\Integration\Mail;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Orchestra\Testbench\TestCase;

class SendingBatchMailTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', [
            'transport' => 'array',
            'batch' => ['size' => 50],
        ]);
    }

    public function testSynchronousNotificationsAreCapturedByABatch()
    {
        $sent = [];

        Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
            $sent[] = $event->data['__laravel_notification'] ?? null;
        });

        $notifiables = [
            (new AnonymousNotifiable)->route('mail', 'taylor@laravel.com'),
            (new AnonymousNotifiable)->route('mail', 'dries@laravel.com'),
        ];

        $result = Mail::batch()->capture(function () use ($notifiables, &$sent) {
            NotificationFacade::send($notifiables, new BatchTestNotification);

            $this->assertSame([], $sent);
        });

        $this->assertCount(2, $result->sent());
        $this->assertSame([BatchTestNotification::class, BatchTestNotification::class], $sent);
        $this->assertCount(2, Mail::mailer('array')->getSymfonyTransport()->messages());
    }
}

class BatchTestNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->subject('Weekly summary')->line('Hello!');
    }
}
