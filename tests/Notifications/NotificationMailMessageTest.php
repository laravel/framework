<?php

namespace Illuminate\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Messages\MailMessage;

class NotificationsMailMessageTest extends TestCase
{
    public function setUp()
    {
        $this->message = new MailMessage;
    }

    public function testTemplate()
    {
        $this->assertEquals('notifications::email', $this->message->markdown);

        $this->message->template('notifications::foo');

        $this->assertEquals('notifications::foo', $this->message->markdown);
    }
}
