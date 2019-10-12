<?php

namespace Illuminate\Tests\Mail;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Swift_Message;

class MailgunTransportTest extends TestCase
{
    public function testSend()
    {
        $message = new Swift_Message('Foo subject', 'Bar body');
        $message->setSender('myself@example.com');
        $message->setTo('me@example.com');

        $client = $this->getMockBuilder(ClientInterface::class)->getMock();
        $transport = new MailgunTransport($client, 'fooKey', 'barDomain');

        // Generate a messageId for our mock to return to ensure that the post-sent message
        // has X-Mailgun-Message-ID in its headers
        $messageId = Str::random(32);
        $sendEmailMock = new sendEmailMock($messageId);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.mailgun.net/v3/barDomain/messages.mime',
                $this->payload($message)
            )
            ->willReturn($sendEmailMock);

        $transport->send($message);

        $this->assertEquals($messageId, $message->getHeaders()->get('X-Mailgun-Message-ID')->getFieldBody());
    }

    private function payload($message)
    {
        return [
            'auth' => [
                'api',
                'fooKey',
            ],
            'multipart' => [
                [
                    'name' => 'to',
                    'contents' => 'me@example.com',
                ],
                [
                    'name' => 'message',
                    'contents' => $message->toString(),
                    'filename' => 'message.mime',
                ],
            ],
        ];
    }
}

class sendEmailMock
{
    protected $messageId;

    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    public function getBody()
    {
        return $this;
    }

    public function getContents()
    {
        return json_encode([
            'id' => $this->messageId,
        ]);
    }
}
