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

    public function testMetadataIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->metadata('origin', 'test-suite');
        $message->metadata('user_id', 1);

        $this->assertArrayHasKey('origin', $message->metadata);
        $this->assertSame('test-suite', $message->metadata['origin']);
        $this->assertArrayHasKey('user_id', $message->metadata);
        $this->assertSame(1, $message->metadata['user_id']);
    }

    public function testTagIsSetCorrectly()
    {
        $message = new MailMessage;
        $message->tag('test');

        $this->assertContains('test', $message->tags);
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

    public function testItAttachesManyFiles()
    {
        $mailMessage = new MailMessage();
        $attachable = new class() implements Attachable
        {
            public function toMailAttachment()
            {
                return Attachment::fromData(fn () => 'bar', 'foo.jpg')->withMime('image/png');
            }
        };

        $mailMessage->attachMany([
            $attachable,
            '/path/to/forge.svg',
            '/path/to/vapor.svg' => [
                'as' => 'Logo.svg',
                'mime' => 'image/svg+xml',
            ],
        ]);

        $this->assertSame([
            [
                'data' => 'bar',
                'name' => 'foo.jpg',
                'options' => [
                    'mime' => 'image/png',
                ],
            ],
        ], $mailMessage->rawAttachments);

        $this->assertSame([
            [
                'file' => '/path/to/forge.svg',
                'options' => [],
            ],
            [
                'file' => '/path/to/vapor.svg',
                'options' => [
                    'as' => 'Logo.svg',
                    'mime' => 'image/svg+xml',
                ],
            ],
        ], $mailMessage->attachments);
    }
}
