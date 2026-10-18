<?php

namespace Illuminate\Support\Testing\Fakes;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\MailQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\BatchResult;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\PendingBatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\ReflectsClosures;
use LogicException;
use PHPUnit\Framework\Assert as PHPUnit;

class MailFake implements Factory, Fake, Mailer, MailQueue
{
    use ForwardsCalls, ReflectsClosures;

    /**
     * The mailer instance.
     *
     * @var MailManager
     */
    public $manager;

    /**
     * The mailer currently being used to send a message.
     *
     * @var string
     */
    protected $currentMailer;

    /**
     * All of the mailables that have been sent.
     *
     * @var array
     */
    protected $mailables = [];

    /**
     * All of the mailables that have been queued.
     *
     * @var array
     */
    protected $queuedMailables = [];

    /**
     * All of the batches that have been sent.
     *
     * @var array<int, array{mailer: string|null, mailables: array}>
     */
    protected $batches = [];

    /**
     * The mailer the batch in progress is being sent with.
     *
     * @var string|null
     */
    protected $batchMailer;

    /**
     * Indicates if a batch is in progress.
     *
     * @var bool
     */
    protected $batching = false;

    /**
     * Create a new mail fake.
     *
     * @param  MailManager  $manager
     */
    public function __construct(MailManager $manager)
    {
        $this->manager = $manager;
        $this->currentMailer = $manager->getDefaultDriver();
    }

    /**
     * Assert if a mailable was sent based on a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|array|string|int|null  $callback
     * @return void
     */
    public function assertSent($mailable, $callback = null)
    {
        [$mailable, $callback] = $this->prepareMailableAndCallback($mailable, $callback);

        if (is_numeric($callback)) {
            return $this->assertSentTimes($mailable, $callback);
        }

        $suggestion = count($this->queuedMailables) ? ' Did you mean to use assertQueued() instead?' : '';

        if (is_array($callback) || is_string($callback)) {
            foreach (Arr::wrap($callback) as $address) {
                $callback = fn ($mail) => $mail->hasTo($address);

                PHPUnit::assertTrue(
                    $this->sent($mailable, $callback)->isNotEmpty(),
                    "The expected [{$mailable}] mailable was not sent to address [{$address}].".$suggestion
                );
            }

            return;
        }

        PHPUnit::assertTrue(
            $this->sent($mailable, $callback)->isNotEmpty(),
            "The expected [{$mailable}] mailable was not sent.".$suggestion
        );
    }

    /**
     * Assert if a mailable was sent a number of times.
     *
     * @param  string  $mailable
     * @param  int  $times
     * @return void
     */
    public function assertSentTimes($mailable, $times = 1)
    {
        $count = $this->sent($mailable)->count();

        PHPUnit::assertSame(
            $times, $count,
            sprintf(
                "The expected [{$mailable}] mailable was sent {$count} %s instead of {$times} %s.",
                Str::plural('time', $count),
                Str::plural('time', $times)
            )
        );
    }

    /**
     * Assert if a mailable was sent exactly once.
     *
     * @param  string  $mailable
     * @return void
     */
    public function assertSentOnce($mailable)
    {
        $this->assertSentTimes($mailable, 1);
    }

    /**
     * Determine if a mailable was not sent or queued to be sent based on a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotOutgoing($mailable, $callback = null)
    {
        $this->assertNotSent($mailable, $callback);
        $this->assertNotQueued($mailable, $callback);
    }

    /**
     * Determine if a mailable was not sent based on a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|array|string|null  $callback
     * @return void
     */
    public function assertNotSent($mailable, $callback = null)
    {
        if (is_string($callback) || is_array($callback)) {
            foreach (Arr::wrap($callback) as $address) {
                $callback = fn ($mail) => $mail->hasTo($address);

                PHPUnit::assertCount(
                    0, $this->sent($mailable, $callback),
                    "The unexpected [{$mailable}] mailable was sent to address [{$address}]."
                );
            }

            return;
        }

        [$mailable, $callback] = $this->prepareMailableAndCallback($mailable, $callback);

        PHPUnit::assertCount(
            0, $this->sent($mailable, $callback),
            "The unexpected [{$mailable}] mailable was sent."
        );
    }

    /**
     * Assert that no mailables were sent or queued to be sent.
     *
     * @return void
     */
    public function assertNothingOutgoing()
    {
        $this->assertNothingSent();
        $this->assertNothingQueued();
    }

    /**
     * Assert that no mailables were sent.
     *
     * @return void
     */
    public function assertNothingSent()
    {
        $mailableNames = (new Collection($this->mailables))->map(
            fn ($mailable) => get_class($mailable)
        )->join("\n- ");

        PHPUnit::assertEmpty($this->mailables, "The following mailables were sent unexpectedly:\n\n- $mailableNames\n");
    }

    /**
     * Assert if a mailable was queued based on a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|array|string|int|null  $callback
     * @return void
     */
    public function assertQueued($mailable, $callback = null)
    {
        [$mailable, $callback] = $this->prepareMailableAndCallback($mailable, $callback);

        if (is_numeric($callback)) {
            return $this->assertQueuedTimes($mailable, $callback);
        }

        if (is_string($callback) || is_array($callback)) {
            foreach (Arr::wrap($callback) as $address) {
                $callback = fn ($mail) => $mail->hasTo($address);

                PHPUnit::assertTrue(
                    $this->queued($mailable, $callback)->isNotEmpty(),
                    "The expected [{$mailable}] mailable was not queued to address [{$address}]."
                );
            }

            return;
        }

        PHPUnit::assertTrue(
            $this->queued($mailable, $callback)->isNotEmpty(),
            "The expected [{$mailable}] mailable was not queued."
        );
    }

    /**
     * Assert if a mailable was queued a number of times.
     *
     * @param  string  $mailable
     * @param  int  $times
     * @return void
     */
    public function assertQueuedTimes($mailable, $times = 1)
    {
        $count = $this->queued($mailable)->count();

        PHPUnit::assertSame(
            $times, $count,
            sprintf(
                "The expected [{$mailable}] mailable was queued {$count} %s instead of {$times} %s.",
                Str::plural('time', $count),
                Str::plural('time', $times)
            )
        );
    }

    /**
     * Assert if a mailable was queued exactly once.
     *
     * @param  string  $mailable
     * @return void
     */
    public function assertQueuedOnce($mailable)
    {
        $this->assertQueuedTimes($mailable, 1);
    }

    /**
     * Determine if a mailable was not queued based on a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|array|string|null  $callback
     * @return void
     */
    public function assertNotQueued($mailable, $callback = null)
    {
        if (is_string($callback) || is_array($callback)) {
            foreach (Arr::wrap($callback) as $address) {
                $callback = fn ($mail) => $mail->hasTo($address);

                PHPUnit::assertCount(
                    0, $this->queued($mailable, $callback),
                    "The unexpected [{$mailable}] mailable was queued to address [{$address}]."
                );
            }

            return;
        }

        [$mailable, $callback] = $this->prepareMailableAndCallback($mailable, $callback);

        PHPUnit::assertCount(
            0, $this->queued($mailable, $callback),
            "The unexpected [{$mailable}] mailable was queued."
        );
    }

    /**
     * Assert that no mailables were queued.
     *
     * @return void
     */
    public function assertNothingQueued()
    {
        $mailableNames = (new Collection($this->queuedMailables))->map(
            fn ($mailable) => get_class($mailable)
        )->join("\n- ");

        PHPUnit::assertEmpty($this->queuedMailables, "The following mailables were queued unexpectedly:\n\n- $mailableNames\n");
    }

    /**
     * Assert the total number of mailables that were sent.
     *
     * @param  int  $count
     * @return void
     */
    public function assertSentCount($count)
    {
        $total = (new Collection($this->mailables))->count();

        PHPUnit::assertSame(
            $count, $total,
            "The total number of mailables sent was {$total} instead of {$count}."
        );
    }

    /**
     * Assert the total number of mailables that were queued.
     *
     * @param  int  $count
     * @return void
     */
    public function assertQueuedCount($count)
    {
        $total = (new Collection($this->queuedMailables))->count();

        PHPUnit::assertSame(
            $count, $total,
            "The total number of mailables queued was {$total} instead of {$count}."
        );
    }

    /**
     * Assert the total number of mailables that were sent or queued.
     *
     * @param  int  $count
     * @return void
     */
    public function assertOutgoingCount($count)
    {
        $total = (new Collection($this->mailables))
            ->concat($this->queuedMailables)
            ->count();

        PHPUnit::assertSame(
            $count, $total,
            "The total number of outgoing mailables was {$total} instead of {$count}."
        );
    }

    /**
     * Assert if a batch was sent based on a truth-test callback.
     *
     * @param  (\Closure(\Illuminate\Support\Collection, string|null): bool)|null  $callback
     * @return void
     */
    public function assertBatched(?Closure $callback = null)
    {
        $batches = (new Collection($this->batches))->filter(
            fn ($batch) => is_null($callback) || $callback(new Collection($batch['mailables']), $batch['mailer'])
        );

        PHPUnit::assertTrue(
            $batches->isNotEmpty(),
            'The expected batch was not sent.'
        );
    }

    /**
     * Assert that no batches were sent.
     *
     * @return void
     */
    public function assertNotBatched()
    {
        PHPUnit::assertEmpty(
            $this->batches,
            sprintf('%d unexpected batches were sent.', count($this->batches))
        );
    }

    /**
     * Assert the total number of batches that were sent.
     *
     * @param  int  $count
     * @return void
     */
    public function assertBatchCount($count)
    {
        PHPUnit::assertCount(
            $count,
            $this->batches,
            sprintf('The number of batches sent was %d instead of %d.', count($this->batches), $count)
        );
    }

    /**
     * Get all of the mailables matching a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function sent($mailable, $callback = null)
    {
        [$mailable, $callback] = $this->prepareMailableAndCallback($mailable, $callback);

        if (! $this->hasSent($mailable)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return $this->mailablesOf($mailable)->filter(fn ($mailable) => $callback($mailable));
    }

    /**
     * Determine if the given mailable has been sent.
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasSent($mailable)
    {
        return $this->mailablesOf($mailable)->isNotEmpty();
    }

    /**
     * Get all of the queued mailables matching a truth-test callback.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function queued($mailable, $callback = null)
    {
        [$mailable, $callback] = $this->prepareMailableAndCallback($mailable, $callback);

        if (! $this->hasQueued($mailable)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return $this->queuedMailablesOf($mailable)->filter(fn ($mailable) => $callback($mailable));
    }

    /**
     * Determine if the given mailable has been queued.
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasQueued($mailable)
    {
        return $this->queuedMailablesOf($mailable)->isNotEmpty();
    }

    /**
     * Get all of the mailed mailables for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function mailablesOf($type)
    {
        return (new Collection($this->mailables))->filter(fn ($mailable) => $mailable instanceof $type);
    }

    /**
     * Get all of the mailed mailables for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function queuedMailablesOf($type)
    {
        return (new Collection($this->queuedMailables))->filter(fn ($mailable) => $mailable instanceof $type);
    }

    /**
     * Get a mailer instance by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public function mailer($name = null)
    {
        $this->currentMailer = $name;

        return $this;
    }

    /**
     * Get a mailer driver instance.
     *
     * @param  string|null  $driver
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public function driver($driver = null)
    {
        return $this->mailer($driver);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users)
    {
        return (new PendingMailFake($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function cc($users)
    {
        return (new PendingMailFake($this))->cc($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users)
    {
        return (new PendingMailFake($this))->bcc($users);
    }

    /**
     * Send a new message with only a raw text part.
     *
     * @param  string  $text
     * @param  \Closure|string  $callback
     * @return void
     */
    public function raw($text, $callback)
    {
        //
    }

    /**
     * Send a new message using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return mixed|void
     */
    public function send($view, array $data = [], $callback = null)
    {
        return $this->sendMail($view, $view instanceof ShouldQueue);
    }

    /**
     * Send a new message synchronously using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $mailable
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return void
     */
    public function sendNow($mailable, array $data = [], $callback = null)
    {
        $this->sendMail($mailable, shouldQueue: false);
    }

    /**
     * Begin a batch of messages, or send the given mailables as a batch.
     *
     * @param  iterable<\Illuminate\Contracts\Mail\Mailable>|null  $mailables
     * @return \Illuminate\Mail\PendingBatch|\Illuminate\Mail\BatchResult
     */
    public function batch($mailables = null)
    {
        $batch = new PendingBatch($this);

        return is_null($mailables) ? $batch : $batch->send($mailables);
    }

    /**
     * Record the mailables sent within the given callback as a batch.
     *
     * @internal
     *
     * @param  \Illuminate\Mail\PendingBatch  $batch
     * @param  \Closure  $callback
     * @return \Illuminate\Mail\BatchResult
     *
     * @throws \LogicException
     */
    public function runBatch(PendingBatch $batch, Closure $callback)
    {
        if ($this->batching) {
            throw new LogicException('A batch is already in progress on this mailer.');
        }

        $this->batching = true;
        $this->batchMailer = $this->currentMailer;

        $sent = count($this->mailables);

        try {
            $callback($this);

            $this->batches[] = [
                'mailer' => $this->batchMailer,
                'mailables' => array_slice($this->mailables, $sent),
            ];
        } finally {
            $this->batching = false;
            $this->batchMailer = null;
        }

        return new BatchResult;
    }

    /**
     * Send a new message using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  bool  $shouldQueue
     * @return mixed|void
     */
    protected function sendMail($view, $shouldQueue = false)
    {
        if (! $view instanceof Mailable) {
            return;
        }

        $view->mailer($this->currentMailer ?? $this->batchMailer);

        if ($shouldQueue) {
            return $this->queue($view);
        }

        $this->currentMailer = null;

        $this->mailables[] = $view;
    }

    /**
     * Queue a new message for sending.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  \BackedEnum|string|null  $queue
     * @return mixed
     */
    public function queue($view, $queue = null)
    {
        if (! $view instanceof Mailable) {
            return;
        }

        if (is_string($queue) || $queue instanceof BackedEnum) {
            $view->onQueue($queue);
        }

        $view->mailer($this->currentMailer);

        $this->currentMailer = null;

        $this->queuedMailables[] = $view;
    }

    /**
     * Queue a new mail message for sending on the given queue.
     *
     * @param  \BackedEnum|string|null  $queue
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function onQueue($queue, $view)
    {
        return $this->queue($view, $queue);
    }

    /**
     * Queue a new mail message for sending on the given queue.
     *
     * @param  \BackedEnum|string|null  $queue
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function queueOn($queue, $view)
    {
        return $this->onQueue($queue, $view);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $view, $queue = null)
    {
        if ($view instanceof Mailable && ! is_null($queue)) {
            $view->onQueue($queue);
        }

        $this->queue($view);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
     *
     * @param  string|null  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function laterOn($queue, $delay, $view)
    {
        return $this->later($delay, $view, $queue);
    }

    /**
     * Infer mailable class using reflection if a typehinted closure is passed to assertion.
     *
     * @param  string|\Closure  $mailable
     * @param  callable|null  $callback
     * @return array
     */
    protected function prepareMailableAndCallback($mailable, $callback)
    {
        if ($mailable instanceof Closure) {
            return [$this->firstClosureParameterType($mailable), $mailable];
        }

        return [$mailable, $callback];
    }

    /**
     * Forget all of the resolved mailer instances.
     *
     * @return $this
     */
    public function forgetMailers()
    {
        $this->currentMailer = null;

        return $this;
    }

    /**
     * Handle dynamic method calls to the mailer.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->manager, $method, $parameters);
    }
}
