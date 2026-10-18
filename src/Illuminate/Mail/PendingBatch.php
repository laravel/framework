<?php

namespace Illuminate\Mail;

use Closure;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use InvalidArgumentException;

class PendingBatch
{
    /**
     * The maximum number of messages per request.
     *
     * @var int|null
     */
    protected $size;

    /**
     * The number of milliseconds to wait between requests.
     *
     * @var int
     */
    protected $interval = 0;

    /**
     * The number of times a failed request should be attempted.
     *
     * @var int
     */
    protected $attempts = 1;

    /**
     * The number of milliseconds to wait between attempts.
     *
     * @var \Closure|int
     */
    protected $retrySleep = 0;

    /**
     * Create a new pending batch instance.
     *
     * @param  \Illuminate\Mail\Mailer|\Illuminate\Support\Testing\Fakes\MailFake  $mailer
     * @param  array  $config
     */
    public function __construct(protected $mailer, array $config = [])
    {
        if (isset($config['size'])) {
            $this->size((int) $config['size']);
        }

        if (isset($config['interval'])) {
            $this->interval((int) $config['interval']);
        }

        if (isset($config['per_second'])) {
            $this->perSecond((int) $config['per_second']);
        }

        if (isset($config['retry'])) {
            $this->retry((int) $config['retry'], $config['retry_sleep'] ?? 0);
        }
    }

    /**
     * Set the maximum number of messages per request.
     *
     * The size is capped at the maximum supported by the mailer's transport.
     *
     * @param  int  $size
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function size(int $size)
    {
        if ($size < 1) {
            throw new InvalidArgumentException('The batch size must be at least 1.');
        }

        $this->size = $size;

        return $this;
    }

    /**
     * Set the number of milliseconds to wait between requests.
     *
     * @param  int  $milliseconds
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function interval(int $milliseconds)
    {
        if ($milliseconds < 0) {
            throw new InvalidArgumentException('The batch interval may not be negative.');
        }

        $this->interval = $milliseconds;

        return $this;
    }

    /**
     * Limit the number of requests made per second.
     *
     * @param  int  $requests
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function perSecond(int $requests)
    {
        if ($requests < 1) {
            throw new InvalidArgumentException('The number of requests per second must be at least 1.');
        }

        return $this->interval((int) ceil(1000 / $requests));
    }

    /**
     * Specify how many times a failed request should be attempted.
     *
     * @param  int  $times
     * @param  \Closure|int  $sleepMilliseconds
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function retry(int $times, Closure|int $sleepMilliseconds = 0)
    {
        if ($times < 1) {
            throw new InvalidArgumentException('The number of attempts must be at least 1.');
        }

        $this->attempts = $times;
        $this->retrySleep = $sleepMilliseconds;

        return $this;
    }

    /**
     * Send the given mailables as a batch.
     *
     * @param  iterable<\Illuminate\Contracts\Mail\Mailable>  $mailables
     * @return \Illuminate\Mail\BatchResult
     *
     * @throws \InvalidArgumentException
     */
    public function send(iterable $mailables)
    {
        return $this->capture(function ($mailer) use ($mailables) {
            foreach ($mailables as $mailable) {
                if (! $mailable instanceof MailableContract) {
                    throw new InvalidArgumentException('Only mailables may be sent in a batch.');
                }

                $mailer->sendNow($mailable);
            }
        });
    }

    /**
     * Capture the messages sent within the given callback and send them as a batch.
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Mail\BatchResult
     */
    public function capture(Closure $callback)
    {
        return $this->mailer->runBatch($this, $callback);
    }

    /**
     * Get the maximum number of messages per request.
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the number of milliseconds to wait between requests.
     *
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Get the number of times a failed request should be attempted.
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Get the number of milliseconds to wait between attempts.
     *
     * @return \Closure|int
     */
    public function getRetrySleep()
    {
        return $this->retrySleep;
    }
}
