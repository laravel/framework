<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ResendTransport;
use Mockery;
use PHPUnit\Framework\TestCase;
use Resend\Collection;
use Resend\Contracts\Client;
use Resend\Email as ResendEmail;
use Resend\Service\Batch as BatchService;
use Resend\Service\Email as EmailService;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
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

    public function testCanBatchEmailsWithoutAttachments(): void
    {
        $transport = new ResendTransport(Mockery::mock(Client::class));

        $this->assertSame(100, $transport->maxBatchSize());
        $this->assertTrue($transport->canBatch((new Email)->text('Hello')));
        $this->assertFalse($transport->canBatch((new Email)->text('Hello')->attach('content', 'file.txt')));
    }

    public function testSendBatch(): void
    {
        $client = Mockery::mock(Client::class);
        $batchService = Mockery::mock(BatchService::class);
        $client->batch = $batchService;

        $batchService->expects('send')
            ->withArgs(function (array $payloads, array $options) {
                return count($payloads) === 2
                    && $payloads[0]['to'] === ['first@example.com']
                    && $payloads[1]['subject'] === 'Second'
                    && ! array_key_exists('attachments', $payloads[0])
                    && $options === ['batch_validation' => 'permissive'];
            })
            ->andReturn(Collection::from([
                'data' => [
                    ResendEmail::from(['id' => 'resend_id_1']),
                    ResendEmail::from(['id' => 'resend_id_2']),
                ],
            ]));

        $messages = [5 => $this->sentMessage('first@example.com', 'First'), 9 => $this->sentMessage('second@example.com', 'Second')];

        $failures = (new ResendTransport($client))->sendBatch($messages);

        $this->assertSame([], $failures);
        $this->assertSame('resend_id_1', $messages[5]->getMessageId());
        $this->assertSame('resend_id_2', $messages[9]->getMessageId());
        $this->assertSame(
            'resend_id_2',
            $messages[9]->getOriginalMessage()->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString()
        );
    }

    public function testSendBatchMapsPermissiveValidationErrorsToTheirMessages(): void
    {
        $client = Mockery::mock(Client::class);
        $batchService = Mockery::mock(BatchService::class);
        $client->batch = $batchService;

        $batchService->expects('send')->andReturn(Collection::from([
            'data' => [
                ResendEmail::from(['id' => 'resend_id_1']),
                ResendEmail::from(['id' => 'resend_id_3']),
            ],
            'errors' => [
                ['index' => 1, 'message' => 'Invalid `to` field'],
            ],
        ]));

        $messages = [
            0 => $this->sentMessage('first@example.com', 'First'),
            1 => $this->sentMessage('second@example.com', 'Second'),
            2 => $this->sentMessage('third@example.com', 'Third'),
        ];

        $failures = (new ResendTransport($client))->sendBatch($messages);

        $this->assertSame([1], array_keys($failures));
        $this->assertInstanceOf(TransportException::class, $failures[1]);
        $this->assertStringContainsString('Invalid `to` field', $failures[1]->getMessage());
        $this->assertSame('resend_id_1', $messages[0]->getMessageId());
        $this->assertSame('resend_id_3', $messages[2]->getMessageId());
    }

    public function testSendBatchUsesTheConfiguredValidationMode(): void
    {
        $client = Mockery::mock(Client::class);
        $batchService = Mockery::mock(BatchService::class);
        $client->batch = $batchService;

        $batchService->expects('send')
            ->withArgs(fn ($payloads, $options) => $options === ['batch_validation' => 'strict'])
            ->andThrow(new \Exception('Validation failed'));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Request to Resend API failed. Reason: Validation failed.');

        (new ResendTransport($client, 'strict'))->sendBatch([
            $this->sentMessage('first@example.com', 'First'),
            $this->sentMessage('second@example.com', 'Second'),
        ]);
    }

    protected function sentMessage(string $to, string $subject): SentMessage
    {
        $email = (new Email)->from('myself@example.com')->to($to)->subject($subject)->text('Body');

        return new SentMessage($email, Envelope::create($email));
    }
}
