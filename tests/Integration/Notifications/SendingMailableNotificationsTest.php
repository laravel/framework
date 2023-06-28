<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class SendingMailableNotificationsTest extends TestCase
{
    public $mailer;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['config']->set('app.locale', 'en');

        View::addLocation(__DIR__.'/Fixtures');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name')->nullable();
        });
    }

    public function testMarkdownNotification()
    {
        $user = MailableNotificationUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);

        $user->notify(new MarkdownNotification());
    }
}


class MailableNotificationUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}


class MarkdownNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->markdown('markdown');
    }
}
