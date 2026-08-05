<?php

namespace Illuminate\Tests\Integration\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Attachment;
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

    public function testCanEmbedInlineImagesInMarkdownNotification()
    {
        $user = MailableNotificationUser::forceCreate([
            'email' => 'nuno@laravel.com',
        ]);

        $user->notify(new InlineImageNotification());

        $message = app('mailer')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $html = $message->getHtmlBody();
        $text = $message->getTextBody();
        $attachments = $message->getAttachments();

        $this->assertCount(3, $attachments);
        $this->assertStringContainsString('Before the image.', $html);
        $this->assertStringContainsString('After the image.', $html);
        $this->assertStringContainsString('Before the image.', $text);
        $this->assertStringContainsString('After the image.', $text);
        $this->assertStringNotContainsString('<img', $text);
        $this->assertStringNotContainsString('cid:', $text);

        foreach ($attachments as $index => $attachment) {
            $this->assertSame('inline', $attachment->getDisposition());
            $this->assertSame('image/png', $attachment->getContentType());
            $this->assertSame(
                ['first.png', 'second.png', 'third.png'][$index],
                $attachment->getFilename(),
            );
            $this->assertSame(
                ['first image', 'second image', 'third image'][$index],
                base64_decode($attachment->bodyToString()),
            );
            $this->assertNotEmpty($attachment->getContentId());
            $this->assertStringContainsString(
                'src="cid:'.$attachment->getContentId().'"',
                $html,
            );
        }

        $this->assertStringContainsString(
            'alt="First image" width="24" height="24" style="vertical-align: middle;"',
            $html,
        );

        $secondImagePosition = strpos($html, 'src="cid:'.$attachments[1]->getContentId());
        $thirdImagePosition = strpos($html, 'src="cid:'.$attachments[2]->getContentId());
        $beforeLinePosition = strpos($html, 'Before the image.');
        $afterLinePosition = strpos($html, 'After the image.');

        $this->assertNotFalse($secondImagePosition);
        $this->assertNotFalse($thirdImagePosition);
        $this->assertNotFalse($beforeLinePosition);
        $this->assertNotFalse($afterLinePosition);
        $this->assertTrue($beforeLinePosition < $secondImagePosition);
        $this->assertTrue($thirdImagePosition < $afterLinePosition);
    }

    public function testCanRenderInlineImages()
    {
        $html = (new InlineImageNotification)->toMail(null)->render()->toHtml();

        $this->assertStringContainsString('src="data:image/png;base64,', $html);
        $this->assertStringNotContainsString('src="cid:', $html);
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

class InlineImageNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Inline image notification')
            ->imageFromData('first image', 'first.png', 'First image', 'image/png', 24, 24, 'vertical-align: middle;')
            ->lineBeforeImage(
                'Before the image.',
                Attachment::fromData(fn () => 'second image', 'second.png')->withMime('image/png'),
                'Second image',
                24,
                24,
                'vertical-align: middle;',
            )
            ->lineAfterImage(
                'After the image.',
                Attachment::fromData(fn () => 'third image', 'third.png')->withMime('image/png'),
                'Third image',
            );
    }
}
