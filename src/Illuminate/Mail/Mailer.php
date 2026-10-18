<?php

namespace Illuminate\Mail;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Transport\BatchTransport;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Sleep;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Throwable;

class Mailer implements MailerContract, MailQueueContract
{
    use Macroable;

    /**
     * The name that is configured for the mailer.
     *
     * @var string
     */
    protected $name;

    /**
     * The view factory instance.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $views;

    /**
     * The Symfony Transport instance.
     *
     * @var \Symfony\Component\Mailer\Transport\TransportInterface
     */
    protected $transport;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The global from address and name.
     *
     * @var array
     */
    protected $from;

    /**
     * The global reply-to address and name.
     *
     * @var array
     */
    protected $replyTo;

    /**
     * The global return path address.
     *
     * @var array
     */
    protected $returnPath;

    /**
     * The global to address and name.
     *
     * @var array
     */
    protected $to;

    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * The default batch configuration.
     *
     * @var array
     */
    protected $batchConfig = [];

    /**
     * The messages captured by the batch in progress.
     *
     * @var array<int, array{0: \Symfony\Component\Mime\Email, 1: array}>|null
     */
    protected $batch;

    /**
     * Create a new Mailer instance.
     *
     * @param  string  $name
     * @param  \Illuminate\Contracts\View\Factory  $views
     * @param  \Symfony\Component\Mailer\Transport\TransportInterface  $transport
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     */
    public function __construct(string $name, Factory $views, TransportInterface $transport, ?Dispatcher $events = null)
    {
        $this->name = $name;
        $this->views = $views;
        $this->events = $events;
        $this->transport = $transport;
    }

    /**
     * Set the global from address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysFrom($address, $name = null)
    {
        $this->from = ['address' => $address, 'name' => $name];
    }

    /**
     * Set the global reply-to address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysReplyTo($address, $name = null)
    {
        $this->replyTo = ['address' => $address, 'name' => $name];
    }

    /**
     * Set the global return path address.
     *
     * @param  string  $address
     * @return void
     */
    public function alwaysReturnPath($address)
    {
        $this->returnPath = ['address' => $address];
    }

    /**
     * Set the global to address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysTo($address, $name = null)
    {
        $this->to = ['address' => $address, 'name' => $name];
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @param  string|null  $name
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users, $name = null)
    {
        if (! is_null($name) && is_string($users)) {
            $users = new Address($users, $name);
        }

        return (new PendingMail($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @param  string|null  $name
     * @return \Illuminate\Mail\PendingMail
     */
    public function cc($users, $name = null)
    {
        if (! is_null($name) && is_string($users)) {
            $users = new Address($users, $name);
        }

        return (new PendingMail($this))->cc($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed  $users
     * @param  string|null  $name
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users, $name = null)
    {
        if (! is_null($name) && is_string($users)) {
            $users = new Address($users, $name);
        }

        return (new PendingMail($this))->bcc($users);
    }

    /**
     * Send a new message with only an HTML part.
     *
     * @param  string  $html
     * @param  mixed  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function html($html, $callback)
    {
        return $this->send(['html' => new HtmlString($html)], [], $callback);
    }

    /**
     * Send a new message with only a raw text part.
     *
     * @param  string  $text
     * @param  mixed  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function raw($text, $callback)
    {
        return $this->send(['raw' => $text], [], $callback);
    }

    /**
     * Send a new message with only a plain part.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  mixed  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function plain($view, array $data, $callback)
    {
        return $this->send(['text' => $view], $data, $callback);
    }

    /**
     * Render the given message as a view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @return string
     */
    public function render($view, array $data = [])
    {
        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        [$view, $plain] = $this->parseView($view);

        $data['message'] = $this->createMessage();

        return $this->replaceEmbeddedAttachments(
            $this->renderView($view ?: $plain, $data),
            $data['message']->getSymfonyMessage()->getAttachments()
        );
    }

    /**
     * Replace the embedded image attachments with raw, inline image data for browser rendering.
     *
     * @param  string  $renderedView
     * @param  array  $attachments
     * @return string
     */
    protected function replaceEmbeddedAttachments(string $renderedView, array $attachments)
    {
        if (preg_match_all('/<img.+?src=[\'"]cid:([^\'"]+)[\'"].*?>/is', $renderedView, $matches)) {
            foreach (array_unique($matches[1]) as $image) {
                foreach ($attachments as $attachment) {
                    if ($attachment->getContentId() === $image || $attachment->getFilename() === $image) {
                        $renderedView = str_replace(
                            'cid:'.$image,
                            'data:'.$attachment->getContentType().';base64,'.$attachment->bodyToString(),
                            $renderedView
                        );

                        break;
                    }
                }
            }
        }

        return $renderedView;
    }

    /**
     * Send a new message using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        $data['mailer'] = $this->name;

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        [$view, $plain, $raw] = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        $this->addContent($message, $view, $plain, $raw, $data);

        if (! is_null($callback)) {
            $callback($message);
        }

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        if (isset($this->to['address'])) {
            $this->setGlobalToAndRemoveCcAndBcc($message);
        }

        // Next we will determine if the message should be sent. We give the developer
        // one final chance to stop this message and then we will send it to all of
        // its recipients. We will then fire the sent event for the sent message.
        $symfonyMessage = $message->getSymfonyMessage();

        if ($this->shouldSendMessage($symfonyMessage, $data)) {
            // When a batch is in progress the message is captured instead of being sent
            // right away. It is sent with the rest of the batch once the batch's done
            // capturing, and the sent event is dispatched for it at that same time.
            if (! is_null($this->batch)) {
                $this->batch[] = [$symfonyMessage, $data];

                return null;
            }

            $symfonySentMessage = $this->sendSymfonyMessage($symfonyMessage);

            if ($symfonySentMessage) {
                $sentMessage = new SentMessage($symfonySentMessage);

                $this->dispatchSentEvent($sentMessage, $data);

                return $sentMessage;
            }
        }
    }

    /**
     * Send the given mailable.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return \Illuminate\Mail\SentMessage|null
     */
    protected function sendMailable(MailableContract $mailable)
    {
        return $mailable instanceof ShouldQueue
            ? $mailable->mailer($this->name)->queue($this->queue)
            : $mailable->mailer($this->name)->send($this);
    }

    /**
     * Send a new message synchronously using a view.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|string|array  $mailable
     * @param  array  $data
     * @param  \Closure|string|null  $callback
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function sendNow($mailable, array $data = [], $callback = null)
    {
        return $mailable instanceof MailableContract
            ? $mailable->mailer($this->name)->send($this)
            : $this->send($mailable, $data, $callback);
    }

    /**
     * Begin a batch of messages, or send the given mailables as a batch.
     *
     * @param  iterable<\Illuminate\Contracts\Mail\Mailable>|null  $mailables
     * @return ($mailables is null ? \Illuminate\Mail\PendingBatch : \Illuminate\Mail\BatchResult)
     */
    public function batch($mailables = null)
    {
        $batch = new PendingBatch($this, $this->batchConfig);

        return is_null($mailables) ? $batch : $batch->send($mailables);
    }

    /**
     * Capture the messages sent within the given callback and send them as a batch.
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
        if (! is_null($this->batch)) {
            throw new LogicException('A batch is already in progress on this mailer.');
        }

        $this->batch = [];

        try {
            $callback($this);

            $messages = $this->batch;
        } finally {
            $this->batch = null;
        }

        return $this->flushBatch($batch, $messages);
    }

    /**
     * Send the captured messages, pacing the requests made to the transport.
     *
     * @param  \Illuminate\Mail\PendingBatch  $batch
     * @param  array<int, array{0: \Symfony\Component\Mime\Email, 1: array}>  $messages
     * @return \Illuminate\Mail\BatchResult
     */
    protected function flushBatch(PendingBatch $batch, array $messages)
    {
        $sent = $failures = [];

        $requests = $this->batchRequests($batch, $messages);

        foreach ($requests as $i => $indexes) {
            if ($i > 0 && $batch->getInterval() > 0) {
                Sleep::usleep($batch->getInterval() * 1000);
            }

            $chunk = array_intersect_key($messages, array_flip($indexes));

            try {
                $results = retry(
                    $batch->getAttempts(),
                    fn () => $this->sendBatchRequest($chunk),
                    $batch->getRetrySleep(),
                );
            } catch (Throwable $e) {
                foreach ($indexes as $index) {
                    $failures[$index] = $e;
                }

                continue;
            }

            foreach ($results as $index => $result) {
                if ($result instanceof Throwable) {
                    $failures[$index] = $result;

                    continue;
                }

                $this->dispatchSentEvent(
                    $sent[$index] = new SentMessage($result), $messages[$index][1]
                );
            }
        }

        ksort($sent);
        ksort($failures);

        return new BatchResult($sent, $failures, count($requests));
    }

    /**
     * Group the captured messages into the requests that will be made to the transport.
     *
     * @param  \Illuminate\Mail\PendingBatch  $batch
     * @param  array<int, array{0: \Symfony\Component\Mime\Email, 1: array}>  $messages
     * @return array<int, array<int, int>>
     */
    protected function batchRequests(PendingBatch $batch, array $messages)
    {
        if (! $this->transport instanceof BatchTransport) {
            return array_map(fn ($index) => [$index], array_keys($messages));
        }

        $max = max(1, $this->transport->maxBatchSize());

        $size = min($batch->getSize() ?? $max, $max);

        $batchable = $individual = [];

        foreach ($messages as $index => [$message]) {
            if ($this->transport->canBatch($message)) {
                $batchable[] = $index;
            } else {
                $individual[] = [$index];
            }
        }

        return [...array_chunk($batchable, $size), ...$individual];
    }

    /**
     * Send a single request to the transport.
     *
     * @param  array<int, array{0: \Symfony\Component\Mime\Email, 1: array}>  $messages
     * @return array<int, \Symfony\Component\Mailer\SentMessage|\Throwable>
     */
    protected function sendBatchRequest(array $messages)
    {
        if (count($messages) === 1) {
            $index = array_key_first($messages);

            $symfonySentMessage = $this->sendSymfonyMessage($messages[$index][0]);

            return $symfonySentMessage ? [$index => $symfonySentMessage] : [];
        }

        $pending = $failures = [];

        foreach ($messages as $index => [$message]) {
            try {
                $pending[$index] = new SymfonySentMessage($message, Envelope::create($message));
            } catch (Throwable $e) {
                $failures[$index] = $e;
            }
        }

        if ($pending !== []) {
            $failures += $this->transport->sendBatch($pending);
        }

        return array_replace($pending, $failures);
    }

    /**
     * Parse the given view name or array.
     *
     * @param  \Closure|array|string  $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view) || $view instanceof Closure) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string|null  $view
     * @param  string|null  $plain
     * @param  string|null  $raw
     * @param  array  $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        if (isset($view)) {
            $message->html($this->renderView($view, $data) ?: ' ');
        }

        if (isset($plain)) {
            $message->text($this->renderView($plain, $data) ?: ' ');
        }

        if (isset($raw)) {
            $message->text($raw);
        }
    }

    /**
     * Render the given view.
     *
     * @param  \Closure|string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView($view, $data)
    {
        $view = value($view, $data);

        return $view instanceof Htmlable
            ? $view->toHtml()
            : $this->views->make($view, $data)->render();
    }

    /**
     * Set the global "to" address on the given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function setGlobalToAndRemoveCcAndBcc($message)
    {
        $message->forgetTo();

        $message->to($this->to['address'], $this->to['name'], true);

        $message->forgetCc();
        $message->forgetBcc();
    }

    /**
     * Queue a new mail message for sending.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @param  \BackedEnum|string|null  $queue
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function queue($view, $queue = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        if (is_string($queue) || $queue instanceof BackedEnum) {
            $view->onQueue($queue);
        }

        return $view->mailer($this->name)->queue($this->queue);
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
     * This method didn't match rest of framework's "onQueue" phrasing. Added "onQueue".
     *
     * @param  string  $queue
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function queueOn($queue, $view)
    {
        return $this->onQueue($queue, $view);
    }

    /**
     * Queue a new mail message for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function later($delay, $view, $queue = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        if (! is_null($queue)) {
            $view->onQueue($queue);
        }

        return $view->mailer($this->name)->later(
            $delay, $this->queue
        );
    }

    /**
     * Queue a new mail message for sending after (n) seconds on the given queue.
     *
     * @param  string  $queue
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Mail\Mailable  $view
     * @return mixed
     */
    public function laterOn($queue, $delay, $view)
    {
        return $this->later($delay, $view, $queue);
    }

    /**
     * Create a new message instance.
     *
     * @return \Illuminate\Mail\Message
     */
    protected function createMessage()
    {
        $message = new Message(new Email());

        // If a global from address has been specified we will set it on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We'll just go ahead and push this address.
        if (! empty($this->from['address'])) {
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instance so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push this address.
        if (! empty($this->replyTo['address'])) {
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        if (! empty($this->returnPath['address'])) {
            $message->returnPath($this->returnPath['address']);
        }

        return $message;
    }

    /**
     * Send a Symfony Email instance.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return \Symfony\Component\Mailer\SentMessage|null
     */
    protected function sendSymfonyMessage(Email $message)
    {
        try {
            return $this->transport->send($message, Envelope::create($message));
        } finally {
            //
        }
    }

    /**
     * Determines if the email can be sent.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @param  array  $data
     * @return bool
     */
    protected function shouldSendMessage($message, $data = [])
    {
        if (! $this->events) {
            return true;
        }

        return $this->events->until(
            new MessageSending($message, $data)
        ) !== false;
    }

    /**
     * Dispatch the message sent event.
     *
     * @param  \Illuminate\Mail\SentMessage  $message
     * @param  array  $data
     * @return void
     */
    protected function dispatchSentEvent($message, $data = [])
    {
        $this->events?->dispatch(
            new MessageSent($message, $data)
        );
    }

    /**
     * Get the Symfony Transport instance.
     *
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function getSymfonyTransport()
    {
        return $this->transport;
    }

    /**
     * Get the view factory instance.
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public function getViewFactory()
    {
        return $this->views;
    }

    /**
     * Set the Symfony Transport instance.
     *
     * @param  \Symfony\Component\Mailer\Transport\TransportInterface  $transport
     * @return void
     */
    public function setSymfonyTransport(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Set the default batch configuration.
     *
     * @param  array  $config
     * @return $this
     */
    public function setBatchConfig(array $config)
    {
        $this->batchConfig = $config;

        return $this;
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }
}
