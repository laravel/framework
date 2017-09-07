<?php

namespace Illuminate\Tests\Mail;

use Aws\Ses\SesClient;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Mail\TransportManager;
use Illuminate\Foundation\Application;
use Illuminate\Mail\Transport\SesTransport;

class MailSesTransportTest extends TestCase
{
    public function testGetTransport()
    {
        /** @var Application $app */
        $app = [
            'config' => new Collection([
                'services.ses' => [
                    'key'    => 'foo',
                    'secret' => 'bar',
                    'region' => 'us-east-1',
                ],
            ]),
        ];

        $manager = new TransportManager($app);

        /** @var SesTransport $transport */
        $transport = $manager->driver('ses');

        /** @var SesClient $ses */
        $ses = $this->readAttribute($transport, 'ses');

        $this->assertEquals('us-east-1', $ses->getRegion());
    }

    public function testSend()
    {
        $message = new \Swift_Message('Foo subject', 'Bar body');
        $message->setSender('myself@example.com');
        $message->setTo('me@example.com');
        $message->setBcc('you@example.com');

        $client = $this->getMockBuilder('Aws\Ses\SesClient')
            ->setMethods(['sendRawEmail'])
            ->disableOriginalConstructor()
            ->getMock();
        $transport = new SesTransport($client);

        // Generate a messageId for our mock to return to ensure that the post-sent message
        // has X-SES-Message-ID in its headers
        $messageId = Str::random(32);
        $sendRawEmailMock = new sendRawEmailMock($messageId);
        $client->expects($this->once())
            ->method('sendRawEmail')
            ->with($this->equalTo([
                'Source' => 'myself@example.com',
                'RawMessage' => ['Data' => (string) $message],
            ]))
            ->willReturn($sendRawEmailMock);

        $transport->send($message);
        $this->assertEquals($messageId, $message->getHeaders()->get('X-SES-Message-ID')->getFieldBody());
    }
}

class sendRawEmailMock
{
    protected $getResponse;

    public function __construct($responseValue)
    {
        $this->getResponse = $responseValue;
    }

    /**
     * Mock the get() call for the sendRawEmail response.
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function get($key)
    {
        return $this->getResponse;
    }
}
