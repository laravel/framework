<?php

namespace Illuminate\Tests\Mail;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\SesV2\SesV2Client;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\SesV2Transport;
use Illuminate\View\Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailSesV2TransportTest extends TestCase
{
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

        $handler = new MockHandler([new Result(['MessageId' => 'ses-message-id'])]);

        (new SesV2Transport($this->sesClient($handler)))->send($message);

        $this->assertSame('SendEmail', $handler->getLastCommand()->getName());

        $arg = $handler->getLastCommand()->toArray();

        $this->assertSame('myself@example.com', $arg['Source']);
        $this->assertSame(['me@example.com', 'you@example.com'], $arg['Destination']['ToAddresses']);
        $this->assertSame(['ContactListName' => 'TestList', 'TopicName' => 'TestTopic'], $arg['ListManagementOptions']);
        $this->assertSame([['Name' => 'FooTag', 'Value' => 'TagValue']], $arg['EmailTags']);
        $this->assertStringContainsString('Reply-To: Taylor Otwell <taylor@example.com>', $arg['Content']['Raw']['Data']);
    }

    public function testSendWithTenantName(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');
        $message->getHeaders()->addTextHeader('X-SES-TENANT-NAME', 'my-tenant');

        $handler = new MockHandler([new Result(['MessageId' => 'ses-message-id'])]);

        (new SesV2Transport($this->sesClient($handler)))->send($message);

        $this->assertSame('my-tenant', $handler->getLastCommand()->toArray()['TenantName']);
    }

    public function testSendWithoutTenantNameDoesNotSetTheOption(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');

        $handler = new MockHandler([new Result(['MessageId' => 'ses-message-id'])]);

        (new SesV2Transport($this->sesClient($handler)))->send($message);

        $this->assertArrayNotHasKey('TenantName', $handler->getLastCommand()->toArray());
    }

    public function testSendError(): void
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');

        $handler = new MockHandler;
        $handler->append(new AwsException('Email address is not verified.', new Command('sendRawEmail')));

        $this->expectException(TransportException::class);

        (new SesV2Transport($this->sesClient($handler)))->send($message);
    }

    protected function sesClient(MockHandler $handler): SesV2Client
    {
        return new SesV2Client([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => ['key' => 'foo', 'secret' => 'bar'],
            'handler' => $handler,
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
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
