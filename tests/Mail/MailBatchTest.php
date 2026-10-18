<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\Mail\BatchSendException;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Mail\Transport\BatchTransport;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use LogicException;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class MailBatchTest extends TestCase
{
    protected function tearDown(): void
    {
        Sleep::fake(false);

        Mockery::close();
    }

    public function testMessagesAreSentOneAtATimeWhenTheTransportDoesNotSupportBatching(): void
    {
        $transport = new ArrayTransport;
        $mailer = $this->mailer($transport);

        $result = $mailer->batch()->capture(function (Mailer $mailer) use ($transport) {
            $this->sendMessages($mailer, 3);

            $this->assertCount(0, $transport->messages());
        });

        $this->assertCount(3, $transport->messages());
        $this->assertCount(3, $result->sent());
        $this->assertSame(3, $result->requests());
        $this->assertTrue($result->successful());
        $this->assertSame([0, 1, 2], array_keys($result->sent()));
    }

    public function testMessagesAreChunkedByTheTransportsMaximumBatchSize(): void
    {
        $transport = new BatchRecordingTransport(max: 2);

        $result = $this->mailer($transport)->batch()->capture(fn ($mailer) => $this->sendMessages($mailer, 5));

        $this->assertSame([['m0', 'm1'], ['m2', 'm3']], $transport->batches);
        $this->assertSame(['m4'], $transport->singles);
        $this->assertSame(3, $result->requests());
        $this->assertCount(5, $result->sent());
    }

    public function testBatchSizeCanBeLoweredButNotRaisedAboveTheTransportsMaximum(): void
    {
        $transport = new BatchRecordingTransport(max: 3);
        $mailer = $this->mailer($transport);

        $mailer->batch()->size(2)->capture(fn ($mailer) => $this->sendMessages($mailer, 4));

        $this->assertSame([['m0', 'm1'], ['m2', 'm3']], $transport->batches);

        $transport->batches = [];

        $mailer->batch()->size(10)->capture(fn ($mailer) => $this->sendMessages($mailer, 4));

        $this->assertSame([['m0', 'm1', 'm2']], $transport->batches);
        $this->assertSame(['m3'], $transport->singles);
    }

    public function testBatchSizeMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->mailer(new ArrayTransport)->batch()->size(0);
    }

    public function testIntervalIsAppliedBetweenRequests(): void
    {
        Sleep::fake();

        $this->mailer(new BatchRecordingTransport(max: 2))
            ->batch()
            ->interval(500)
            ->capture(fn ($mailer) => $this->sendMessages($mailer, 5));

        Sleep::assertSequence([
            Sleep::for(500)->milliseconds(),
            Sleep::for(500)->milliseconds(),
        ]);
    }

    public function testIntervalIsAppliedBetweenMessagesWhenFallingBackToALoop(): void
    {
        Sleep::fake();

        $this->mailer(new ArrayTransport)
            ->batch()
            ->perSecond(4)
            ->capture(fn ($mailer) => $this->sendMessages($mailer, 3));

        Sleep::assertSequence([
            Sleep::for(250)->milliseconds(),
            Sleep::for(250)->milliseconds(),
        ]);
    }

    public function testDefaultsCanBeConfiguredPerMailer(): void
    {
        Sleep::fake();

        $transport = new BatchRecordingTransport(max: 100);

        $mailer = $this->mailer($transport)->setBatchConfig(['size' => 2, 'interval' => 100]);

        $mailer->batch()->capture(fn ($mailer) => $this->sendMessages($mailer, 4));

        $this->assertSame([['m0', 'm1'], ['m2', 'm3']], $transport->batches);

        Sleep::assertSequence([Sleep::for(100)->milliseconds()]);
    }

    public function testMessagesTheTransportCannotBatchAreSentIndividually(): void
    {
        $transport = new BatchRecordingTransport(max: 10, unbatchable: ['m1']);

        $result = $this->mailer($transport)->batch()->capture(fn ($mailer) => $this->sendMessages($mailer, 3));

        $this->assertSame([['m0', 'm2']], $transport->batches);
        $this->assertSame(['m1'], $transport->singles);
        $this->assertCount(3, $result->sent());
    }

    public function testPerMessageFailuresAreReportedAndSentEventsAreOnlyDispatchedForSentMessages(): void
    {
        $events = new Dispatcher;
        $sent = [];

        $events->listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
            $sent[] = $event->message->getSubject();
        });

        $transport = new BatchRecordingTransport(max: 10, rejected: ['m1']);

        $result = $this->mailer($transport, $events)->batch()->capture(fn ($mailer) => $this->sendMessages($mailer, 3));

        $this->assertSame(['m0', 'm2'], $sent);
        $this->assertSame([0, 2], array_keys($result->sent()));
        $this->assertSame([1], array_keys($result->failures()));
        $this->assertTrue($result->failed());
        $this->assertCount(3, $result);

        $this->expectException(BatchSendException::class);
        $this->expectExceptionMessage('1 of 3 messages in the batch failed to send.');

        $result->throw();
    }

    public function testSentEventsAreDispatchedAfterTheRequestIsMade(): void
    {
        $events = new Dispatcher;
        $ids = [];

        $events->listen(MessageSent::class, function (MessageSent $event) use (&$ids) {
            $ids[] = $event->sent->getMessageId();
        });

        $this->mailer(new BatchRecordingTransport(max: 10), $events)
            ->batch()
            ->capture(function ($mailer) use (&$ids) {
                $this->sendMessages($mailer, 2);

                $this->assertSame([], $ids);
            });

        $this->assertSame(['id-m0', 'id-m1'], $ids);
    }

    public function testFailedRequestsAreRetried(): void
    {
        Sleep::fake();

        $transport = new BatchRecordingTransport(max: 10, failures: 1);

        $result = $this->mailer($transport)
            ->batch()
            ->retry(2, 50)
            ->capture(fn ($mailer) => $this->sendMessages($mailer, 2));

        $this->assertTrue($result->successful());
        $this->assertSame([['m0', 'm1']], $transport->batches);

        Sleep::assertSequence([Sleep::for(50)->milliseconds()]);
    }

    public function testEveryMessageInAFailedRequestIsReportedAsFailed(): void
    {
        $transport = new BatchRecordingTransport(max: 2, failures: 1);

        $result = $this->mailer($transport)->batch()->capture(fn ($mailer) => $this->sendMessages($mailer, 3));

        $this->assertSame([0, 1], array_keys($result->failures()));
        $this->assertInstanceOf(RuntimeException::class, $result->failures()[0]);
        $this->assertSame([2], array_keys($result->sent()));
    }

    public function testNothingIsSentWhenTheCallbackThrows(): void
    {
        $transport = new ArrayTransport;
        $mailer = $this->mailer($transport);

        try {
            $mailer->batch()->capture(function ($mailer) {
                $this->sendMessages($mailer, 2);

                throw new RuntimeException('Whoops');
            });
        } catch (RuntimeException) {
            //
        }

        $this->assertCount(0, $transport->messages());

        $mailer->html('<p>After</p>', fn (Message $message) => $message->to('a@example.com')->from('b@example.com'));

        $this->assertCount(1, $transport->messages());
    }

    public function testBatchesCannotBeNested(): void
    {
        $mailer = $this->mailer(new ArrayTransport);

        $this->expectException(LogicException::class);

        $mailer->batch()->capture(fn ($mailer) => $mailer->batch()->capture(fn () => null));
    }

    public function testMessagesCancelledBySendingListenersAreNotCaptured(): void
    {
        $events = new Dispatcher;
        $events->listen(MessageSending::class, fn (MessageSending $event) => $event->message->getSubject() !== 'm1');

        $transport = new BatchRecordingTransport(max: 10);

        $result = $this->mailer($transport, $events)->batch()->capture(fn ($mailer) => $this->sendMessages($mailer, 3));

        $this->assertSame([['m0', 'm2']], $transport->batches);
        $this->assertCount(2, $result);
    }

    public function testMailablesCanBeSentAsABatch(): void
    {
        $transport = new BatchRecordingTransport(max: 10);

        $result = $this->mailer($transport)->batch([
            new BatchTestMailable('first'),
            new BatchTestQueuedMailable('second'),
        ]);

        $this->assertSame([['first', 'second']], $transport->batches);
        $this->assertCount(2, $result->sent());
    }

    public function testOnlyMailablesCanBeSentAsABatch(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->mailer(new ArrayTransport)->batch(['not a mailable']);
    }

    protected function mailer($transport, $events = null): Mailer
    {
        $view = Mockery::mock(Factory::class);

        $mailer = new Mailer('batch', $view, $transport, $events);
        $mailer->alwaysFrom('hello@example.com');

        return $mailer;
    }

    protected function sendMessages(Mailer $mailer, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $mailer->html("<p>Message {$i}</p>", function (Message $message) use ($i) {
                $message->to("user{$i}@example.com")->subject("m{$i}");
            });
        }
    }
}

class BatchRecordingTransport extends AbstractTransport implements BatchTransport
{
    public array $batches = [];

    public array $singles = [];

    public function __construct(
        protected int $max,
        protected array $unbatchable = [],
        protected array $rejected = [],
        protected int $failures = 0,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $subject = MessageConverter::toEmail($message->getOriginalMessage())->getSubject();

        $this->singles[] = $subject;

        $message->setMessageId("id-{$subject}");
    }

    public function maxBatchSize(): int
    {
        return $this->max;
    }

    public function canBatch(Email $email): bool
    {
        return ! in_array($email->getSubject(), $this->unbatchable, true);
    }

    public function sendBatch(array $messages): array
    {
        if ($this->failures > 0) {
            $this->failures--;

            throw new RuntimeException('Request failed.');
        }

        $failures = [];

        foreach ($messages as $index => $message) {
            $subject = $message->getOriginalMessage()->getSubject();

            if (in_array($subject, $this->rejected, true)) {
                $failures[$index] = new RuntimeException("Rejected {$subject}.");
            } else {
                $message->setMessageId("id-{$subject}");
            }
        }

        $this->batches[] = array_map(fn ($message) => $message->getOriginalMessage()->getSubject(), array_values($messages));

        return $failures;
    }

    public function __toString(): string
    {
        return 'batch-recording';
    }
}

class BatchTestMailable extends Mailable
{
    public function __construct(public string $label)
    {
        //
    }

    public function build()
    {
        return $this->to("{$this->label}@example.com")->subject($this->label)->html("<p>{$this->label}</p>");
    }
}

class BatchTestQueuedMailable extends BatchTestMailable implements ShouldQueue
{
    //
}
