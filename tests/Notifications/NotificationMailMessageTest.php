<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\TestCase;

class NotificationMailMessageTest extends TestCase
{
    public function testTemplate()
    {
        $message = new MailMessage;

        $this->assertSame('notifications::email', $message->markdown);

        $message->template('notifications::foo');

        $this->assertSame('notifications::foo', $message->markdown);
    }

    public function testHtmlAndPlainView()
    {
        $message = new MailMessage;

        $this->assertNull($message->view);
        $this->assertSame([], $message->viewData);

        $message->view(['notifications::foo', 'notifications::bar'], [
            'foo' => 'bar',
        ]);

        $this->assertSame('notifications::foo', $message->view[0]);
        $this->assertSame('notifications::bar', $message->view[1]);
        $this->assertSame(['foo' => 'bar'], $message->viewData);
    }

    public function testHtmlView()
    {
        $message = new MailMessage;

        $this->assertNull($message->view);
        $this->assertSame([], $message->viewData);

        $message->view('notifications::foo', [
            'foo' => 'bar',
        ]);

        $this->assertSame('notifications::foo', $message->view);
        $this->assertSame(['foo' => 'bar'], $message->viewData);
    }

    public function testPlainView()
    {
        $message = new MailMessage;

        $this->assertNull($message->view);
        $this->assertSame([], $message->viewData);

        $message->view([null, 'notifications::foo'], [
            'foo' => 'bar',
        ]);

        $this->assertSame('notifications::foo', $message->view[1]);
        $this->assertSame(['foo' => 'bar'], $message->viewData);
    }

    public function testCcIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->cc('test@example.com');

        $this->assertSame([['test@example.com', null]], $message->cc);

        $message = new MailMessage;
        $message->cc('test@example.com')
                ->cc('test@example.com', 'Test');

        $this->assertSame([['test@example.com', null], ['test@example.com', 'Test']], $message->cc);

        $message = new MailMessage;
        $message->cc(['test@example.com', 'Test' => 'test@example.com']);

        $this->assertSame([['test@example.com', null], ['test@example.com', 'Test']], $message->cc);
    }

    public function testBccIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->bcc('test@example.com');

        $this->assertSame([['test@example.com', null]], $message->bcc);

        $message = new MailMessage;
        $message->bcc('test@example.com')
                ->bcc('test@example.com', 'Test');

        $this->assertSame([['test@example.com', null], ['test@example.com', 'Test']], $message->bcc);

        $message = new MailMessage;
        $message->bcc(['test@example.com', 'Test' => 'test@example.com']);

        $this->assertSame([['test@example.com', null], ['test@example.com', 'Test']], $message->bcc);
    }

    public function testReplyToIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->replyTo('test@example.com');

        $this->assertSame([['test@example.com', null]], $message->replyTo);

        $message = new MailMessage;
        $message->replyTo('test@example.com')
                ->replyTo('test@example.com', 'Test');

        $this->assertSame([['test@example.com', null], ['test@example.com', 'Test']], $message->replyTo);

        $message = new MailMessage;
        $message->replyTo(['test@example.com', 'Test' => 'test@example.com']);

        $this->assertSame([['test@example.com', null], ['test@example.com', 'Test']], $message->replyTo);
    }

    public function testCallbackIsSetCorrectly()
    {
        $callback = function () {
            //
        };

        $message = new MailMessage;
        $message->withSwiftMessage($callback);

        $this->assertSame([$callback], $message->callbacks);
    }
}
