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

        $message = new MailMessage;
        $message->cc('test@example.com', 'Test')
            ->cc(['test@example.com', 'test2@example.com']);

        $this->assertSame([
            ['test@example.com', 'Test'],
            ['test@example.com', null],
            ['test2@example.com', null],
        ], $message->cc);
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

        $message = new MailMessage;
        $message->bcc('test@example.com', 'Test')
            ->bcc(['test@example.com', 'test2@example.com']);

        $this->assertSame([
            ['test@example.com', 'Test'],
            ['test@example.com', null],
            ['test2@example.com', null],
        ], $message->bcc);
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

        $message = new MailMessage;
        $message->replyTo('test@example.com', 'Test')
            ->replyTo(['test@example.com', 'test2@example.com']);

        $this->assertSame([
            ['test@example.com', 'Test'],
            ['test@example.com', null],
            ['test2@example.com', null],
        ], $message->replyTo);
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

    public function testActionsAreIncludedInToArray()
    {
        $message = new MailMessage;
        $message->action('Accept', 'https://example.com/accept')
                ->action('Reject', 'https://example.com/reject');

        $array = $message->toArray();

        $this->assertArrayHasKey('actions', $array);
        $this->assertSame([
            ['text' => 'Accept', 'url' => 'https://example.com/accept'],
            ['text' => 'Reject', 'url' => 'https://example.com/reject'],
        ], $array['actions']);

        $this->assertSame('Reject', $array['actionText']);
        $this->assertSame('https://example.com/reject', $array['actionUrl']);
    }

    public function testBackwardCompatibilityWithSingleAction()
    {
        $message = new MailMessage;
        $message->action('Single Action', 'https://example.com');

        $array = $message->toArray();

        $this->assertSame('Single Action', $array['actionText']);
        $this->assertSame('https://example.com', $array['actionUrl']);
        $this->assertCount(1, $array['actions']);
        $this->assertSame('Single Action', $array['actions'][0]['text']);
        $this->assertSame('https://example.com', $array['actions'][0]['url']);
    }

    public function testEmptyActionsArray()
    {
        $message = new MailMessage;

        $this->assertSame([], $message->actions);

        $array = $message->toArray();
        $this->assertSame([], $array['actions']);
        $this->assertNull($array['actionText']);
        $this->assertNull($array['actionUrl']);
    }

    public function testSingleActionIsProvidedToTemplate()
    {
        $message = new MailMessage;
        $message->subject('Test Subject')
                ->action('Single Action', 'https://example.com/single');

        $data = $message->data();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(1, $data['actions']);
        $this->assertSame('Single Action', $data['actions'][0]['text']);
        $this->assertSame('https://example.com/single', $data['actions'][0]['url']);

        $this->assertSame('Single Action', $data['actionText']);
        $this->assertSame('https://example.com/single', $data['actionUrl']);
    }

    public function testMultipleActionsAreProvidedToTemplate()
    {
        $message = new MailMessage;
        $message->subject('Test Subject')
                ->action('Accept', 'https://example.com/accept')
                ->action('Reject', 'https://example.com/reject')
                ->action('Review', 'https://example.com/review');

        $data = $message->data();

        $this->assertArrayHasKey('actions', $data);
        $this->assertCount(3, $data['actions']);
        
        $this->assertSame('Accept', $data['actions'][0]['text']);
        $this->assertSame('https://example.com/accept', $data['actions'][0]['url']);
        
        $this->assertSame('Reject', $data['actions'][1]['text']);
        $this->assertSame('https://example.com/reject', $data['actions'][1]['url']);
        
        $this->assertSame('Review', $data['actions'][2]['text']);
        $this->assertSame('https://example.com/review', $data['actions'][2]['url']);

        $this->assertTrue(!empty($data['actions']));
        $this->assertFalse(empty($data['actions']));
    }

    public function testLineMethodPlacementWithActions()
    {
        $message = new MailMessage;
        $message->line('Intro line 1')
                ->line('Intro line 2')
                ->action('Accept', 'https://example.com/accept')
                ->line('Outro line 1')
                ->action('Reject', 'https://example.com/reject')
                ->line('Outro line 2');

        $data = $message->data();

        $this->assertCount(2, $data['introLines']);
        $this->assertSame('Intro line 1', $data['introLines'][0]);
        $this->assertSame('Intro line 2', $data['introLines'][1]);

        $this->assertCount(2, $data['outroLines']);
        $this->assertSame('Outro line 1', $data['outroLines'][0]);
        $this->assertSame('Outro line 2', $data['outroLines'][1]);

        $this->assertCount(2, $data['actions']);
        $this->assertSame('Accept', $data['actions'][0]['text']);
        $this->assertSame('Reject', $data['actions'][1]['text']);
    }

    public function testInterleavedContentOrder()
    {
        $message = new MailMessage;
        $message->line('Intro line 1')
                ->line('Intro line 2')
                ->action('Accept', 'https://example.com/accept')
                ->line('Outro line 1')
                ->action('Reject', 'https://example.com/reject')
                ->line('Outro line 2');

        $data = $message->data();

        $this->assertArrayHasKey('content', $data);
        $this->assertCount(6, $data['content']);

        $this->assertInstanceOf(\Illuminate\Notifications\Line::class, $data['content'][0]);
        $this->assertSame('Intro line 1', $data['content'][0]->content);

        $this->assertInstanceOf(\Illuminate\Notifications\Line::class, $data['content'][1]);
        $this->assertSame('Intro line 2', $data['content'][1]->content);

        $this->assertInstanceOf(\Illuminate\Notifications\Action::class, $data['content'][2]);
        $this->assertSame('Accept', $data['content'][2]->text);
        $this->assertSame('https://example.com/accept', $data['content'][2]->url);

        $this->assertInstanceOf(\Illuminate\Notifications\Line::class, $data['content'][3]);
        $this->assertSame('Outro line 1', $data['content'][3]->content);

        $this->assertInstanceOf(\Illuminate\Notifications\Action::class, $data['content'][4]);
        $this->assertSame('Reject', $data['content'][4]->text);
        $this->assertSame('https://example.com/reject', $data['content'][4]->url);

        $this->assertInstanceOf(\Illuminate\Notifications\Line::class, $data['content'][5]);
        $this->assertSame('Outro line 2', $data['content'][5]->content);
    }
}
