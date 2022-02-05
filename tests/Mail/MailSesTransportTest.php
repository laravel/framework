<?php

namespace Illuminate\Tests\Mail;

use Aws\Ses\SesClient;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class MailSesTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

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
        $transport = $manager->createSymfonyTransport(['transport' => 'ses']);

        $ses = $transport->ses();

        $this->assertSame('us-east-1', $ses->getRegion());

        $this->assertSame('ses', (string) $transport);
    }

    public function testSend()
    {
        $message = new Email();
        $message->subject('Foo subject');
        $message->text('Bar body');
        $message->sender('myself@example.com');
        $message->to('me@example.com');
        $message->bcc('you@example.com');

        $client = m::mock(SesClient::class);
        $client->shouldReceive('sendRawEmail')->once();

        (new SesTransport($client))->send($message);
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
                                'Tags' => [
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
        $transport = $mailer->getSymfonyTransport();

        $this->assertSame('eu-west-1', $transport->ses()->getRegion());

        $this->assertSame([
            'ConfigurationSetName' => 'Laravel',
            'Tags' => [
                ['Name' => 'Laravel', 'Value' => 'Framework'],
            ],
        ], $transport->getOptions());
    }
}
