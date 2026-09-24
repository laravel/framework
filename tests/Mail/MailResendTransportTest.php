<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ResendTransport;
use Mockery;
use PHPUnit\Framework\TestCase;
use Resend\Contracts\Client;
use Resend\Email as ResendEmail;
use Resend\Service\Email as EmailService;
use Symfony\Component\Mime\Email;

class MailResendTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetTransport(): void
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'services.resend' => [
                    'key' => 'foo',
                ],
            ]);
        });

        $manager = new MailManager($container);

        /** @var \Illuminate\Mail\Transport\ResendTransport $transport */
        $transport = $manager->createSymfonyTransport(['transport' => 'resend', 'key' => 'foo']);

        $this->assertSame('resend', (string) $transport);
    }

    public function testSend(): void
    {
        $email = new Email();
        $email->subject('Foo subject');
        $email->text('Bar body');
        $email->sender('myself@example.com');
        $email->to('me@example.com');

        $client = Mockery::mock(Client::class);
        $emailService = Mockery::mock(EmailService::class);
        $client->emails = $emailService;

        $emailService->expects('send')
            ->andReturn(ResendEmail::from([
                'id' => 'resend_id_test',
                'from' => 'myself@example.com',
                'to' => 'me@example.com',
                'created_at' => '2023-04-08T00:00:00.000Z',
            ]));

        $transport = new ResendTransport($client);
        $sentMessage = $transport->send($email);

        $this->assertSame('resend_id_test', $sentMessage->getMessageId());
        $this->assertSame(
            'resend_id_test',
            $sentMessage->getOriginalMessage()->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString()
        );
    }
}
