<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\LettermintTransport;
use Illuminate\View\Factory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class MailLettermintTransportTest extends TestCase
{
    public function testGetTransport(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'services' => [
                    'lettermint' => [
                        'token' => 'service-token',
                        'route' => 'broadcast',
                    ],
                ],
            ]);
        });

        $manager = new MailManager($container);

        $transport = $manager->createSymfonyTransport(['transport' => 'lettermint']);

        $this->assertInstanceOf(LettermintTransport::class, $transport);
        $this->assertSame('lettermint', (string) $transport);
        $this->assertSame('service-token', (new ReflectionProperty($transport, 'token'))->getValue($transport));
        $this->assertSame('broadcast', (new ReflectionProperty($transport, 'route'))->getValue($transport));
    }

    public function testGetTransportUsesServiceKeyFallback(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'services' => [
                    'lettermint' => [
                        'key' => 'service-key',
                    ],
                ],
            ]);
        });

        $manager = new MailManager($container);

        $transport = $manager->createSymfonyTransport(['transport' => 'lettermint']);

        $this->assertSame('service-key', (new ReflectionProperty($transport, 'token'))->getValue($transport));
    }

    public function testMailerConfigurationOverridesServiceConfiguration(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'mail' => [
                    'default' => 'lettermint',
                    'mailers' => [
                        'lettermint' => [
                            'transport' => 'lettermint',
                            'token' => 'mailer-token',
                            'route' => 'transactional',
                        ],
                    ],
                ],
                'services' => [
                    'lettermint' => [
                        'token' => 'service-token',
                        'route' => 'broadcast',
                    ],
                ],
            ]);
        });

        $container->instance('view', $this->createStub(Factory::class));
        $container->singleton('events', fn () => null);

        $manager = new MailManager($container);

        $transport = $manager->mailer('lettermint')->getSymfonyTransport();

        $this->assertSame('mailer-token', (new ReflectionProperty($transport, 'token'))->getValue($transport));
        $this->assertSame('transactional', (new ReflectionProperty($transport, 'route'))->getValue($transport));
    }

    public function testSend(): void
    {
        $requestBody = null;
        $requestUrl = null;
        $requestHeaders = null;

        $client = new MockHttpClient(function ($method, $url, $options) use (&$requestBody, &$requestUrl, &$requestHeaders) {
            $requestUrl = $url;
            $requestBody = json_decode($options['body'], true);
            $requestHeaders = $options['normalized_headers'];

            return new MockResponse(json_encode([
                'message_id' => 'lettermint-message-id',
                'status' => 'pending',
            ]), ['http_code' => 202]);
        });

        $transport = new LettermintTransport('test-token', 'broadcast', $client);

        $sender = new Address('sender@example.com', 'Taylor Otwell');
        $recipient = new Address('me@example.com', 'Acme');
        $replyTo = new Address('taylor@example.com', 'Taylor Otwell');

        $message = new Email();
        $message->subject('Test subject');
        $message->html('<p>Hello</p>');
        $message->text('Hello');
        $message->sender($sender);
        $message->to($recipient);
        $message->cc('cc@example.com');
        $message->bcc('bcc@example.com');
        $message->replyTo($replyTo);
        $message->getHeaders()->addTextHeader('X-Custom-Header', 'CustomValue');
        $message->getHeaders()->add(new TagHeader('transactional'));
        $message->getHeaders()->add(new MetadataHeader('campaign', 'welcome'));

        $sentMessage = $transport->send($message);

        $this->assertSame('https://api.lettermint.co/v1/send', $requestUrl);
        $this->assertSame(['x-lettermint-token: test-token'], $requestHeaders['x-lettermint-token']);
        $this->assertSame($sender->toString(), $requestBody['from']);
        $this->assertSame([$recipient->toString()], $requestBody['to']);
        $this->assertSame(['cc@example.com'], $requestBody['cc']);
        $this->assertSame(['bcc@example.com'], $requestBody['bcc']);
        $this->assertSame([$replyTo->toString()], $requestBody['reply_to']);
        $this->assertSame('Test subject', $requestBody['subject']);
        $this->assertSame('<p>Hello</p>', $requestBody['html']);
        $this->assertSame('Hello', $requestBody['text']);
        $this->assertSame('broadcast', $requestBody['route']);
        $this->assertSame('transactional', $requestBody['tag']);
        $this->assertSame(['campaign' => 'welcome'], $requestBody['metadata']);
        $this->assertSame(['X-Custom-Header' => 'CustomValue'], $requestBody['headers']);
        $this->assertSame(
            'lettermint-message-id',
            $sentMessage->getOriginalMessage()->getHeaders()->get('X-Lettermint-Message-ID')->getBodyAsString()
        );
    }

    public function testSendWithAttachments(): void
    {
        $requestBody = null;

        $client = new MockHttpClient(function ($method, $url, $options) use (&$requestBody) {
            $requestBody = json_decode($options['body'], true);

            return new MockResponse(json_encode([
                'message_id' => 'lettermint-message-id',
                'status' => 'pending',
            ]), ['http_code' => 202]);
        });

        $transport = new LettermintTransport('test-token', null, $client);

        $message = new Email();
        $message->subject('With attachments');
        $message->text('See attached');
        $message->sender('sender@example.com');
        $message->to('me@example.com');
        $message->attach('file contents', 'document.txt', 'text/plain');
        $message->addPart((new DataPart('image-bytes', 'logo.png', 'image/png'))->asInline()->setContentId('logo@example.com'));

        $transport->send($message);

        $this->assertCount(2, $requestBody['attachments']);
        $this->assertSame('document.txt', $requestBody['attachments'][0]['filename']);
        $this->assertSame('text/plain', $requestBody['attachments'][0]['content_type']);
        $this->assertNotEmpty($requestBody['attachments'][0]['content']);
        $this->assertSame('logo.png', $requestBody['attachments'][1]['filename']);
        $this->assertSame('image/png', $requestBody['attachments'][1]['content_type']);
        $this->assertNotEmpty($requestBody['attachments'][1]['content']);
        $this->assertSame('logo@example.com', $requestBody['attachments'][1]['content_id']);
    }

    public function testSendThrowsOnApiFailure(): void
    {
        $client = new MockHttpClient(function () {
            return new MockResponse(json_encode([
                'message' => "The domain 'example.com' is not verified or does not belong to your account.",
                'errors' => [
                    'from' => ["The domain 'example.com' is not verified or does not belong to your account."],
                ],
            ]), ['http_code' => 422]);
        });

        $transport = new LettermintTransport('test-token', null, $client);

        $message = new Email();
        $message->subject('Fail');
        $message->text('Body');
        $message->sender('sender@example.com');
        $message->to('me@example.com');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage("The domain 'example.com' is not verified");

        $transport->send($message);
    }

    public function testSendThrowsOnRequestFailure(): void
    {
        $client = new MockHttpClient(function () {
            throw new \RuntimeException('Connection refused');
        });

        $transport = new LettermintTransport('test-token', null, $client);

        $message = new Email();
        $message->subject('Fail');
        $message->text('Body');
        $message->sender('sender@example.com');
        $message->to('me@example.com');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Connection refused');

        $transport->send($message);
    }
}
