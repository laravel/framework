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

        $app['config']->set('mail.markdown.theme', 'blank');

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
            'email' => 'nuno@laravel.com',
        ]);

        $user->notify(new MarkdownNotification());

        $email = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage()->toString();

        $cid = explode(' cid:', str($email)->explode("\r\n")
            ->filter(fn ($line) => str_contains($line, 'Embed content: cid:'))
            ->first())[1];

        $this->assertStringContainsString(<<<EOT
        Content-Type: application/x-php; name=$cid\r
        Content-Transfer-Encoding: base64\r
        Content-Disposition: inline; name=$cid; filename=$cid\r
        EOT, $email);
    }

    public function testCanSetTheme()
    {
        $user = MailableNotificationUser::forceCreate([
            'email' => 'nuno@laravel.com',
        ]);

        $user->notify(new MarkdownNotification('color-test'));
        $mailTransport = app('mailer')->getSymfonyTransport();

        $contents = $mailTransport->messages()[0]->getOriginalMessage()->toString();
        $this->assertStringContainsString('<body style=3D"color: test;">', $contents);

        // confirm passing no theme resets to the app's default theme
        $user->notify(new MarkdownNotification());

        $contents = $mailTransport->messages()[1]->getOriginalMessage()->toString();
        $this->assertStringNotContainsString('<body style=3D"color: test;">', $contents);
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
    public function __construct(
        protected $theme = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)->markdown('markdown');

        if (! is_null($this->theme)) {
            $message->theme($this->theme);
        }

        return $message;
    }
}
