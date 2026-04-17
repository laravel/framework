<?php

namespace Illuminate\Tests\Mail;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\CloudflareTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailCloudflareTransportTest extends TestCase
{
    public function testGetTransport(): void
    {
        $container = new Container;

        $manager = new MailManager($container);

        $transport = $manager->createSymfonyTransport([
            'transport' => 'cloudflare',
            'account_id' => 'test-account-id',
            'key' => 'test-key',
        ]);

        $this->assertInstanceOf(CloudflareTransport::class, $transport);
        $this->assertSame('cloudflare', (string) $transport);
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
                'success' => true,
                'errors' => [],
                'messages' => [],
                'result' => [
                    'delivered' => ['me@example.com'],
                    'permanent_bounces' => [],
                    'queued' => [],
                ],
            ]), ['http_code' => 200]);
        });

        $transport = new CloudflareTransport('test-account-id', 'test-key', $client);

        $message = new Email();
        $message->subject('Test subject');
        $message->html('<p>Hello</p>');
        $message->text('Hello');
        $message->sender('sender@example.com');
        $message->to('me@example.com');
        $message->cc('cc@example.com');
        $message->bcc('bcc@example.com');
        $message->replyTo('taylor@example.com');
        $message->getHeaders()->addTextHeader('X-Custom-Header', 'CustomValue');

        $transport->send($message);

        $this->assertStringContainsString('test-account-id', $requestUrl);
        $this->assertSame('https://api.cloudflare.com/client/v4/accounts/test-account-id/email/sending/send', $requestUrl);
        $this->assertSame('sender@example.com', $requestBody['from']);
        $this->assertSame(['me@example.com'], $requestBody['to']);
        $this->assertSame(['cc@example.com'], $requestBody['cc']);
        $this->assertSame(['bcc@example.com'], $requestBody['bcc']);
        $this->assertSame('taylor@example.com', $requestBody['reply_to']);
        $this->assertSame('Test subject', $requestBody['subject']);
        $this->assertSame('<p>Hello</p>', $requestBody['html']);
        $this->assertSame('Hello', $requestBody['text']);
        $this->assertSame('CustomValue', $requestBody['headers']['X-Custom-Header']);
        $this->assertArrayHasKey('authorization', $requestHeaders);
        $this->assertSame(['Authorization: Bearer test-key'], $requestHeaders['authorization']);
    }

    public function testSendWithNamedAddresses(): void
    {
        $requestBody = null;
        $requestUrl = null;
        $requestHeaders = null;

        $client = new MockHttpClient(function ($method, $url, $options) use (&$requestBody, &$requestUrl, &$requestHeaders) {
            $requestUrl = $url;
            $requestBody = json_decode($options['body'], true);
            $requestHeaders = $options['normalized_headers'];

            return new MockResponse(json_encode([
                'success' => true,
                'errors' => [],
                'messages' => [],
                'result' => [
                    'delivered' => ['me@example.com'],
                    'permanent_bounces' => [],
                    'queued' => [],
                ],
            ]), ['http_code' => 200]);
        });

        $transport = new CloudflareTransport('test-account-id', 'test-key', $client);

        $message = new Email();
        $message->subject('Test subject');
        $message->text('Hello');
        $message->sender(new Address('sender@example.com', 'Taylor Otwell'));
        $message->to('me@example.com');
        $message->replyTo(new Address('taylor@example.com', 'Taylor Otwell'));

        $transport->send($message);

        $this->assertSame([
            'name' => 'Taylor Otwell',
            'address' => 'sender@example.com',
        ], $requestBody['from']);
        $this->assertSame([
            'name' => 'Taylor Otwell',
            'address' => 'taylor@example.com',
        ], $requestBody['reply_to']);
    }

    public function testSendWithAttachment(): void
    {
        $requestBody = null;

        $client = new MockHttpClient(function ($method, $url, $options) use (&$requestBody) {
            $requestBody = json_decode($options['body'], true);

            return new MockResponse(json_encode([
                'success' => true,
                'errors' => [],
                'messages' => [],
                'result' => ['delivered' => ['me@example.com'], 'permanent_bounces' => [], 'queued' => []],
            ]), ['http_code' => 200]);
        });

        $transport = new CloudflareTransport('test-account-id', 'test-key', $client);

        $message = new Email();
        $message->subject('With attachment');
        $message->text('See attached');
        $message->sender('sender@example.com');
        $message->to('me@example.com');
        $message->attach('file contents', 'document.txt', 'text/plain');

        $transport->send($message);

        $this->assertCount(1, $requestBody['attachments']);
        $this->assertSame('document.txt', $requestBody['attachments'][0]['filename']);
        $this->assertSame('text/plain', $requestBody['attachments'][0]['type']);
        $this->assertSame('attachment', $requestBody['attachments'][0]['disposition']);
        $this->assertNotEmpty($requestBody['attachments'][0]['content']);
    }

    public function testSendThrowsOnApiFailure(): void
    {
        $client = new MockHttpClient(function () {
            return new MockResponse(json_encode([
                'success' => false,
                'errors' => [
                    [
                        'code' => 10001,
                        'message' => 'invalid_request_schema'
                    ]
                ],
                'messages' => [],
                'result' => null,
            ]), ['http_code' => 400]);
        });

        $transport = new CloudflareTransport('test-account-id', 'test-key', $client);

        $message = new Email();
        $message->subject('Fail');
        $message->text('Body');
        $message->sender('sender@example.com');
        $message->to('me@example.com');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('invalid_request_schema');

        $transport->send($message);
    }
}
