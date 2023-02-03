<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Attachment;
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

        $this->assertSame([['name' => null, 'address' => 'test@example.com']], $message->cc);

        $message = new MailMessage;
        $message->cc('test@example.com')
                ->cc('joe@example.com', 'Test');

        $this->assertSame([['name' => null, 'address' => 'test@example.com'], ['name' => 'Test', 'address' => 'joe@example.com']], $message->cc);

        $message = new MailMessage;
        $message->cc(['joe@example.com', ['name' => 'Test', 'email' => 'test@example.com']]);

        $this->assertSame([['name' => null, 'address' => 'joe@example.com'], ['name' => 'Test', 'address' => 'test@example.com']], $message->cc);
    }

    public function testBccIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->bcc('test@example.com');

        $this->assertSame([['name' => null, 'address' => 'test@example.com']], $message->bcc);

        $message = new MailMessage;
        $message->bcc('test@example.com')
                ->bcc('joe@example.com', 'Test');

        $this->assertSame([['name' => null, 'address' => 'test@example.com'], ['name' => 'Test', 'address' => 'joe@example.com']], $message->bcc);

        $message = new MailMessage;
        $message->bcc(['joe@example.com', ['name' => 'Test', 'email' => 'test@example.com']]);

        $this->assertSame([['name' => null, 'address' => 'joe@example.com'], ['name' => 'Test', 'address' => 'test@example.com']], $message->bcc);
    }

    public function testReplyToIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->replyTo('test@example.com');

        $this->assertSame([['name' => null, 'address' => 'test@example.com']], $message->replyTo);

        $message = new MailMessage;
        $message->replyTo('test@example.com')
                ->replyTo('joe@example.com', 'Test');

        $this->assertSame([['name' => null, 'address' => 'test@example.com'], ['name' => 'Test', 'address' => 'joe@example.com']], $message->replyTo);

        $message = new MailMessage;
        $message->replyTo(['joe@example.com', ['name' => 'Test', 'email' => 'test@example.com']]);

        $this->assertSame([['name' => null, 'address' => 'joe@example.com'], ['name' => 'Test', 'address' => 'test@example.com']], $message->replyTo);
    }

    public function testMetadataIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->metadata('origin', 'test-suite');
        $message->metadata('user_id', 1);

        $this->assertTrue($message->hasMetadata('origin', 'test-suite'));
        $this->assertTrue($message->hasMetadata('user_id', 1));
    }

    public function testTagIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->tag('test');

        $message->assertHasTag('test');
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
        $this->assertSame([['name' => null, 'address' => 'cc@example.com']], $message->cc);

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
        $this->assertSame([['name' => null, 'address' => 'cc@example.com']], $message->cc);
        $this->assertSame([['name' => null, 'address' => 'bcc@example.com']], $message->bcc);

        $message = new MailMessage;
        $message->when(false, $callback)->bcc('bcc@example.com');
        $this->assertSame([], $message->cc);
        $this->assertSame([['name' => null, 'address' => 'bcc@example.com']], $message->bcc);
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
        $this->assertSame([['name' => null, 'address' => 'truthy@example.com']], $message->cc);

        $message = new MailMessage;
        $message->when(0, $callback, $default);
        $this->assertSame([['name' => null, 'address' => 'zero@example.com']], $message->cc);
    }

    public function testUnlessCallback()
    {
        $callback = function (MailMessage $mailMessage, $condition) {
            $this->assertFalse($condition);

            $mailMessage->cc('test@example.com');
        };

        $message = new MailMessage;
        $message->unless(false, $callback);
        $this->assertSame([['name' => null, 'address' => 'test@example.com']], $message->cc);

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
        $this->assertSame([['name' => null, 'address' => 'cc@example.com']], $message->cc);
        $this->assertSame([['name' => null, 'address' => 'bcc@example.com']], $message->bcc);

        $message = new MailMessage;
        $message->unless(true, $callback)->bcc('bcc@example.com');
        $this->assertSame([], $message->cc);
        $this->assertSame([['name' => null, 'address' => 'bcc@example.com']], $message->bcc);
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
        $this->assertSame([['name' => null, 'address' => 'zero@example.com']], $message->cc);

        $message = new MailMessage;
        $message->unless('truthy', $callback, $default);
        $this->assertSame([['name' => null, 'address' => 'truthy@example.com']], $message->cc);
    }

    public function testItAttachesFilesViaAttachableContractFromPath()
    {
        $message = new MailMessage;

        $message->attach(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromPath('/foo.jpg')->as('bar')->withMime('image/png');
            }
        });

        $this->assertSame([
            'file' => '/foo.jpg',
            'options' => [
                'as' => 'bar',
                'mime' => 'image/png',
            ],
        ], $message->attachments[0]);
    }

    public function testItAttachesFilesViaAttachableContractFromData()
    {
        $mailMessage = new MailMessage();

        $mailMessage->attach(new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromData(fn () => 'bar', 'foo.jpg')->withMime('image/png');
            }
        });

        $this->assertSame([
            'data' => 'bar',
            'name' => 'foo.jpg',
            'options' => [
                'mime' => 'image/png',
            ],
        ], $mailMessage->rawAttachments[0]);
    }
}
