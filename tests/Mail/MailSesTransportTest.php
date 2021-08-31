<?php

namespace Illuminate\Tests\Mail;

use Aws\SesV2\SesV2Client;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Str;
use Illuminate\View\Factory;
use PHPUnit\Framework\TestCase;
use Swift_Message;

class MailSesTransportTest extends TestCase
{
    /** @group Foo */
    public function testGetTransport()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'services.ses' => [
                    'key' => 'foo',
                    'secret' => 'bar',
                    'region' => 'us-east-1',
                ],
            ]);
        });

        $manager = new MailManager($container);

        /** @var \Illuminate\Mail\Transport\SesTransport $transport */
        $transport = $manager->createTransport(['transport' => 'ses']);

        $ses = $transport->ses();

        $this->assertSame('us-east-1', $ses->getRegion());
    }

    public function testSend()
    {
        $message = new Swift_Message('Foo subject', 'Bar body');
        $message->setSender('myself@example.com');
        $message->setTo('me@example.com');
        $message->setBcc('you@example.com');

        $client = $this->getMockBuilder(SesV2Client::class)
            ->addMethods(['sendEmail'])
            ->disableOriginalConstructor()
            ->getMock();
        $transport = new SesTransport($client);

        // Generate a messageId for our mock to return to ensure that the post-sent message
        // has X-Message-ID in its headers
        $messageId = Str::random(32);
        $sendRawEmailMock = new sendRawEmailMock($messageId);
        $client->expects($this->once())
            ->method('sendEmail')
            ->with($this->equalTo([
                'Content' => [
                    'Raw' => ['Data' => (string) $message],
                ],
            ]))
            ->willReturn($sendRawEmailMock);

        $transport->send($message);

        $this->assertEquals($messageId, $message->getHeaders()->get('X-Message-ID')->getFieldBody());
        $this->assertEquals($messageId, $message->getHeaders()->get('X-SES-Message-ID')->getFieldBody());
    }

    public function testSesLocalConfiguration()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'mail' => [
                    'mailers' => [
                        'ses' => [
                            'transport' => 'ses',
                            'region' => 'eu-west-1',
                            'options' => [
                                'ConfigurationSetName' => 'Laravel',
                                'EmailTags' => [
                                    ['Name' => 'Laravel', 'Value' => 'Framework'],
                                ],
                            ],
                        ],
                    ],
                ],
                'services' => [
                    'ses' => [
                        'region' => 'us-east-1',
                    ],
                ],
            ]);
        });

        $container->instance('view', $this->createMock(Factory::class));

        $container->bind('events', function () {
            return null;
        });

        $manager = new MailManager($container);

        /** @var \Illuminate\Mail\Mailer $mailer */
        $mailer = $manager->mailer('ses');

        /** @var \Illuminate\Mail\Transport\SesTransport $transport */
        $transport = $mailer->getSwiftMailer()->getTransport();

        $this->assertSame('eu-west-1', $transport->ses()->getRegion());

        $this->assertSame([
            'ConfigurationSetName' => 'Laravel',
            'EmailTags' => [
                ['Name' => 'Laravel', 'Value' => 'Framework'],
            ],
        ], $transport->getOptions());
    }
}

class sendRawEmailMock
{
    protected $getResponse;

    public function __construct($responseValue)
    {
        $this->getResponse = $responseValue;
    }

    public function get($key)
    {
        return $this->getResponse;
    }
}
