<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Stringable;
use Orchestra\Testbench\TestCase;

class SendingMailableNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        $app['config']->set('mail.driver', 'array');

        $app['config']->set('app.locale', 'en');

        $app['config']->set('mail.markdown.theme', 'blank');

        $app['view']->addLocation(__DIR__.'/Fixtures');
    }

    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name')->nullable();
        });
    }

    protected function beforeRefreshingDatabase()
    {
        Schema::dropIfExists('users');
    }

    public function testMarkdownNotification()
    {
        $user = MailableNotificationUser::forceCreate([
            'email' => 'nuno@laravel.com',
        ]);

        $user->notify(new MarkdownNotification());

        $message = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $email = $message->toString();
        $textBody = $message->getTextBody();

        $cid = explode(' cid:', (new Stringable($textBody))->explode("\n")
            ->filter(fn ($line) => str_contains($line, 'Embed content: cid:'))
            ->first())[1];

        $filename = explode(' file: ', (new Stringable($textBody))->explode("\n")
            ->filter(fn ($line) => str_contains($line, 'Embed file: '))
            ->first())[1];

        $this->assertStringContainsString(<<<EOT
        Content-Type: application/x-php; name=$filename\r
        Content-Transfer-Encoding: base64\r
        Content-Disposition: inline; name=$filename;\r
         filename=$filename\r
        Content-ID: <$cid>\r
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
