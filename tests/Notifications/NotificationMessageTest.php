<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\Messages\SimpleMessage as Message;
use PHPUnit\Framework\TestCase;

class NotificationMessageTest extends TestCase
{
    public function testLevelCanBeRetrieved()
    {
        $message = new Message;
        $this->assertSame('info', $message->level);

        $message = new Message;
        $message->level('error');
        $this->assertSame('error', $message->level);
    }

    public function testMailerCanBeSetFromString()
    {
        $message = new Message;
        $message->mailer('postmark');

        $this->assertSame('postmark', $message->mailer);
    }

    public function testMailerCanBeSetFromBackedEnum()
    {
        $message = new Message;
        $message->mailer(NotificationMessageMailerEnum::Postmark);

        $this->assertSame('postmark', $message->mailer);
    }

    public function testMailerCanBeSetFromUnitEnum()
    {
        $message = new Message;
        $message->mailer(NotificationMessageMailerUnitEnum::Postmark);

        $this->assertSame('Postmark', $message->mailer);
    }

    public function testMessageFormatsMultiLineText()
    {
        $message = new Message;
        $message->with('
            This is a
            single line of text.
        ');

        $this->assertSame('This is a single line of text.', $message->introLines[0]);

        $message = new Message;
        $message->with([
            'This is a',
            'single line of text.',
        ]);

        $this->assertSame('This is a single line of text.', $message->introLines[0]);
    }
}

enum NotificationMessageMailerEnum: string
{
    case Postmark = 'postmark';
}

enum NotificationMessageMailerUnitEnum
{
    case Postmark;
}
