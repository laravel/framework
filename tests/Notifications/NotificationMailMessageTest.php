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
        $message->withSymfonyMessage($callback);

        $this->assertSame([$callback], $message->callbacks);
    }

    public function testWhenCallback()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertTrue($condition);

            $mailMessage->cc('cc@example.com');
        };

        $message = new MailMessage;
        $message->when(true, $callback);
        $this->assertSame([['cc@example.com', null]], $message->cc);

        $message = new MailMessage;
        $message->when(false, $callback);
        $this->assertSame([], $message->cc);
    }

    public function testWhenCallbackWithReturn()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertTrue($condition);

            return $mailMessage->cc('cc@example.com');
        };

        $message = new MailMessage;
        $message->when(true, $callback)->bcc('bcc@example.com');
        $this->assertSame([['cc@example.com', null]], $message->cc);
        $this->assertSame([['bcc@example.com', null]], $message->bcc);

        $message = new MailMessage;
        $message->when(false, $callback)->bcc('bcc@example.com');
        $this->assertSame([], $message->cc);
        $this->assertSame([['bcc@example.com', null]], $message->bcc);
    }

    public function testWhenCallbackWithDefault()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertSame('truthy', $condition);

            $mailMessage->cc('truthy@example.com');
        };

        $default = function (MailMessage $mailMessage, $condition) {
            $this->assertEquals(0, $condition);

            $mailMessage->cc('zero@example.com');
        };

        $message = new MailMessage;
        $message->when('truthy', $callback, $default);
        $this->assertSame([['truthy@example.com', null]], $message->cc);

        $message = new MailMessage;
        $message->when(0, $callback, $default);
        $this->assertSame([['zero@example.com', null]], $message->cc);
    }

    public function testUnlessCallback()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertFalse($condition);

            $mailMessage->cc('test@example.com');
        };

        $message = new MailMessage;
        $message->unless(false, $callback);
        $this->assertSame([['test@example.com', null]], $message->cc);

        $message = new MailMessage;
        $message->unless(true, $callback);
        $this->assertSame([], $message->cc);
    }

    public function testUnlessCallbackWithReturn()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertFalse($condition);

            return $mailMessage->cc('cc@example.com');
        };

        $message = new MailMessage;
        $message->unless(false, $callback)->bcc('bcc@example.com');
        $this->assertSame([['cc@example.com', null]], $message->cc);
        $this->assertSame([['bcc@example.com', null]], $message->bcc);

        $message = new MailMessage;
        $message->unless(true, $callback)->bcc('bcc@example.com');
        $this->assertSame([], $message->cc);
        $this->assertSame([['bcc@example.com', null]], $message->bcc);
    }

    public function testUnlessCallbackWithDefault()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertEquals(0, $condition);

            $mailMessage->cc('zero@example.com');
        };

        $default = function (MailMessage $mailMessage, $condition) {
            $this->assertSame('truthy', $condition);

            $mailMessage->cc('truthy@example.com');
        };

        $message = new MailMessage;
        $message->unless(0, $callback, $default);
        $this->assertSame([['zero@example.com', null]], $message->cc);

        $message = new MailMessage;
        $message->unless('truthy', $callback, $default);
        $this->assertSame([['truthy@example.com', null]], $message->cc);
    }
}
