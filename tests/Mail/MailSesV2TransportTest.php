<?php

namespace Illuminate\Tests\Mail;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\SesV2\SesV2Client;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\SesV2Transport;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailSesV2TransportTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testGetTransport(): void
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

        /** @var \Illuminate\Mail\Transport\SesV2Transport $transport */
        $transport = $manager->createSymfonyTransport(['transport' => 'ses-v2']);

        $ses = $transport->ses();

        $this->assertSame('us-east-1', $ses->getRegion());

        $this->assertSame('ses-v2', (string) $transport);
    }

    public function testSend(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');
        $message->bcc('you@example.com');
        $message->replyTo(new Address('taylor@example.com', 'Taylor Otwell'));
        $message->getHeaders()->add(new MetadataHeader('FooTag', 'TagValue'));
        $message->getHeaders()->addTextHeader('X-SES-LIST-MANAGEMENT-OPTIONS', 'contactListName=TestList;topicName=TestTopic');

        $client = m::mock(SesV2Client::class);
        $sesResult = m::mock();
        $sesResult->shouldReceive('get')
            ->with('MessageId')
            ->once()
            ->andReturn('ses-message-id');
        $client->shouldReceive('sendEmail')->once()
            ->with(m::on(function ($arg) {
                return $arg['Source'] === 'myself@example.com' &&
                    $arg['Destination']['ToAddresses'] === ['me@example.com', 'you@example.com'] &&
                    $arg['ListManagementOptions'] === ['ContactListName' => 'TestList', 'TopicName' => 'TestTopic'] &&
                    $arg['EmailTags'] === [['Name' => 'FooTag', 'Value' => 'TagValue']] &&
                    str_contains($arg['Content']['Raw']['Data'], 'Reply-To: Taylor Otwell <taylor@example.com>');
            }))
            ->andReturn($sesResult);

        (new SesV2Transport($client))->send($message);
    }

    public function testSendError(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');

        $client = m::mock(SesV2Client::class);
        $client->shouldReceive('sendEmail')->once()
            ->andThrow(new AwsException('Email address is not verified.', new Command('sendRawEmail')));

        $this->expectException(TransportException::class);

        (new SesV2Transport($client))->send($message);
    }

    public function testSesV2LocalConfiguration(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'mail' => [
                    'mailers' => [
                        'ses' => [
                            'transport' => 'ses-v2',
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

        /** @var \Illuminate\Mail\Transport\SesV2Transport $transport */
        $transport = $mailer->getSymfonyTransport();

        $this->assertSame('eu-west-1', $transport->ses()->getRegion());

        $this->assertSame([
            'ConfigurationSetName' => 'Laravel',
            'EmailTags' => [
                ['Name' => 'Laravel', 'Value' => 'Framework'],
            ],
        ], $transport->getOptions());
    }
}
