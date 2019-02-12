<?php

namespace Illuminate\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Messages\SimpleMessage as Message;

class NotificationMessageTest extends TestCase
{
    public function testLevelCanBeRetrieved()
    {
        $message = new Message;
        $this->assertEquals('info', $message->level);

        $message = new Message;
        $message->level('error');
        $this->assertEquals('error', $message->level);
    }

    public function testMessageFormatsMultiLineText()
    {
        $message = new Message;
        $message->with('
            This is a
            single line of text.
        ');

        $this->assertEquals('This is a single line of text.', $message->introLines[0]);

        $message = new Message;
        $message->with([
            'This is a',
            'single line of text.',
        ]);

        $this->assertEquals('This is a single line of text.', $message->introLines[0]);
    }
}
