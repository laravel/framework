<?php

namespace Illuminate\Mail;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Localizable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\Constraints\SeeInOrder;
use PHPUnit\Framework\Assert as PHPUnit;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Address;

class Mailable implements MailableContract, Renderable
{
    use Conditionable, ForwardsCalls, Localizable, Macroable {
        __call as macroCall;
    }

    /**
     * The locale of the message.
     *
     * @var string
     */
    public $locale;

    /**
     * The person the message is from.
     *
     * @var array
     */
    public $from = [];

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    public $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    public $bcc = [];

    /**
     * The "reply to" recipients of the message.
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * The subject of the message.
     *
     * @var string
     */
    public $subject;

    /**
     * The Markdown template for the message (if applicable).
     *
     * @var string
     */
    public $markdown;

    /**
     * The HTML to use for the message.
     *
     * @var string
     */
    protected $html;

    /**
     * The view to use for the message.
     *
     * @var string
     */
    public $view;

    /**
     * The plain text view to use for the message.
     *
     * @var string
     */
    public $textView;

    /**
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The attachments for the message.
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The raw attachments for the message.
     *
     * @var array
     */
    public $rawAttachments = [];

    /**
     * The attachments from a storage disk.
     *
     * @var array
     */
    public $diskAttachments = [];

    /**
     * The tags for the message.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The metadata for the message.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * The callbacks for the message.
     *
     * @var array
     */
    public $callbacks = [];

    /**
     * The name of the theme that should be used when formatting the message.
     *
     * @var string|null
     */
    public $theme;

    /**
     * The name of the mailer that should send the message.
     *
     * @var string
     */
    public $mailer;

    /**
     * The rendered mailable views for testing / assertions.
     *
     * @var array
     */
    protected $assertionableRenderStrings;

    /**
     * The callback that should be invoked while building the view data.
     *
     * @var callable
     */
    public static $viewDataCallback;

    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($mailer)
    {
        return $this->withLocale($this->locale, function () use ($mailer) {
            Container::getInstance()->call([$this, 'build']);

            $mailer = $mailer instanceof MailFactory
                            ? $mailer->mailer($this->mailer)
                            : $mailer;

            return $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
                $this->buildFrom($message)
                     ->buildRecipients($message)
                     ->buildSubject($message)
                     ->buildTags($message)
                     ->buildMetadata($message)
                     ->runCallbacks($message)
                     ->buildAttachments($message);
            });
        });
    }

    /**
     * Queue the message for sending.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function queue(Queue $queue)
    {
        if (isset($this->delay)) {
            return $this->later($this->delay, $queue);
        }

        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->pushOn(
            $queueName ?: null, $this->newQueuedJob()
        );
    }

    /**
     * Deliver the queued message after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function later($delay, Queue $queue)
    {
        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->laterOn(
            $queueName ?: null, $delay, $this->newQueuedJob()
        );
    }

    /**
     * Make the queued mailable job instance.
     *
     * @return mixed
     */
    protected function newQueuedJob()
    {
        return (new SendQueuedMailable($this))
                    ->through(array_merge(
                        method_exists($this, 'middleware') ? $this->middleware() : [],
                        $this->middleware ?? []
                    ));
    }

    /**
     * Render the mailable into a view.
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function render()
    {
        return $this->withLocale($this->locale, function () {
            Container::getInstance()->call([$this, 'build']);

            return Container::getInstance()->make('mailer')->render(
                $this->buildView(), $this->buildViewData()
            );
        });
    }

    /**
     * Build the view for the message.
     *
     * @return array|string
     *
     * @throws \ReflectionException
     */
    protected function buildView()
    {
        if (isset($this->html)) {
            return array_filter([
                'html' => new HtmlString($this->html),
                'text' => $this->textView ?? null,
            ]);
        }

        if (isset($this->markdown)) {
            return $this->buildMarkdownView();
        }

        if (isset($this->view, $this->textView)) {
            return [$this->view, $this->textView];
        } elseif (isset($this->textView)) {
            return ['text' => $this->textView];
        }

        return $this->view;
    }

    /**
     * Build the Markdown view for the message.
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function buildMarkdownView()
    {
        $markdown = Container::getInstance()->make(Markdown::class);

        if (isset($this->theme)) {
            $markdown->theme($this->theme);
        }

        $data = $this->buildViewData();

        return [
            'html' => $markdown->render($this->markdown, $data),
            'text' => $this->buildMarkdownText($markdown, $data),
        ];
    }

    /**
     * Build the view data for the message.
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function buildViewData()
    {
        $data = $this->viewData;

        if (static::$viewDataCallback) {
            $data = array_merge($data, call_user_func(static::$viewDataCallback, $this));
        }

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Build the text view for a Markdown message.
     *
     * @param  \Illuminate\Mail\Markdown  $markdown
     * @param  array  $data
     * @return string
     */
    protected function buildMarkdownText($markdown, $data)
    {
        return $this->textView
                ?? $markdown->renderText($this->markdown, $data);
    }

    /**
     * Add the sender to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildFrom($message)
    {
        if (! empty($this->from)) {
            $message->from($this->from[0]['address'], $this->from[0]['name']);
        }

        return $this;
    }

    /**
     * Add all of the recipients to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildRecipients($message)
    {
        foreach (['to', 'cc', 'bcc', 'replyTo'] as $type) {
            foreach ($this->{$type} as $recipient) {
                $message->{$type}($recipient['address'], $recipient['name']);
            }
        }

        return $this;
    }

    /**
     * Set the subject for the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildSubject($message)
    {
        if ($this->subject) {
            $message->subject($this->subject);
        } else {
            $message->subject(Str::title(Str::snake(class_basename($this), ' ')));
        }

        return $this;
    }

    /**
     * Add all of the attachments to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildAttachments($message)
    {
        foreach ($this->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $message->attachData(
                $attachment['data'], $attachment['name'], $attachment['options']
            );
        }

        $this->buildDiskAttachments($message);

        return $this;
    }

    /**
     * Add all of the disk attachments to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function buildDiskAttachments($message)
    {
        foreach ($this->diskAttachments as $attachment) {
            $storage = Container::getInstance()->make(
                FilesystemFactory::class
            )->disk($attachment['disk']);

            $message->attachData(
                $storage->get($attachment['path']),
                $attachment['name'] ?? basename($attachment['path']),
                array_merge(['mime' => $storage->mimeType($attachment['path'])], $attachment['options'])
            );
        }
    }

    /**
     * Add all defined tags to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildTags($message)
    {
        if ($this->tags) {
            foreach ($this->tags as $tag) {
                $message->getHeaders()->add(new TagHeader($tag));
            }
        }

        return $this;
    }

    /**
     * Add all defined metadata to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildMetadata($message)
    {
        if ($this->metadata) {
            foreach ($this->metadata as $key => $value) {
                $message->getHeaders()->add(new MetadataHeader($key, $value));
            }
        }

        return $this;
    }

    /**
     * Run the callbacks for the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function runCallbacks($message)
    {
        foreach ($this->callbacks as $callback) {
            $callback($message->getSymfonyMessage());
        }

        return $this;
    }

    /**
     * Set the locale of the message.
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the priority of this message.
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level = 3)
    {
        $this->callbacks[] = function ($message) use ($level) {
            $message->priority($level);
        };

        return $this;
    }

    /**
     * Set the sender of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        return $this->setAddress($address, $name, 'from');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasFrom($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'from');
    }

    /**
     * Set the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function to($address, $name = null)
    {
        return $this->setAddress($address, $name, 'to');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasTo($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'to');
    }

    /**
     * Set the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        return $this->setAddress($address, $name, 'cc');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasCc($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'cc');
    }

    /**
     * Set the recipients of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        return $this->setAddress($address, $name, 'bcc');
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasBcc($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'bcc');
    }

    /**
     * Set the "reply to" address of the message.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        return $this->setAddress($address, $name, 'replyTo');
    }

    /**
     * Determine if the given replyTo is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasReplyTo($address, $name = null)
    {
        return $this->hasRecipient($address, $name, 'replyTo');
    }

    /**
     * Set the recipients of the message.
     *
     * All recipients are stored internally as [['name' => ?, 'address' => ?]]
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @param  string  $property
     * @return $this
     */
    protected function setAddress($address, $name = null, $property = 'to')
    {
        if (empty($address)) {
            return $this;
        }

        foreach ($this->addressesToArray($address, $name) as $recipient) {
            $recipient = $this->normalizeRecipient($recipient);

            $this->{$property}[] = [
                'name' => $recipient->name ?? null,
                'address' => $recipient->email,
            ];
        }

        return $this;
    }

    /**
     * Convert the given recipient arguments to an array.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return array
     */
    protected function addressesToArray($address, $name)
    {
        if (! is_array($address) && ! $address instanceof Collection) {
            $address = is_string($name) ? [['name' => $name, 'email' => $address]] : [$address];
        }

        return $address;
    }

    /**
     * Convert the given recipient into an object.
     *
     * @param  mixed  $recipient
     * @return object
     */
    protected function normalizeRecipient($recipient)
    {
        if (is_array($recipient)) {
            if (array_values($recipient) === $recipient) {
                return (object) array_map(function ($email) {
                    return compact('email');
                }, $recipient);
            }

            return (object) $recipient;
        } elseif (is_string($recipient)) {
            return (object) ['email' => $recipient];
        } elseif ($recipient instanceof Address) {
            return (object) ['email' => $recipient->getAddress(), 'name' => $recipient->getName()];
        }

        return $recipient;
    }

    /**
     * Determine if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @param  string  $property
     * @return bool
     */
    protected function hasRecipient($address, $name = null, $property = 'to')
    {
        if (empty($address)) {
            return false;
        }

        $expected = $this->normalizeRecipient(
            $this->addressesToArray($address, $name)[0]
        );

        $expected = [
            'name' => $expected->name ?? null,
            'address' => $expected->email,
        ];

        return collect($this->{$property})->contains(function ($actual) use ($expected) {
            if (! isset($expected['name'])) {
                return $actual['address'] == $expected['address'];
            }

            return $actual == $expected;
        });
    }

    /**
     * Set the subject of the message.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Determine if the mailable has the given subject.
     *
     * @param  string  $subject
     * @return bool
     */
    public function hasSubject($subject)
    {
        return $this->subject === $subject;
    }

    /**
     * Set the Markdown template for the message.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function markdown($view, array $data = [])
    {
        $this->markdown = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the view and view data for the message.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the rendered HTML content for the message.
     *
     * @param  string  $html
     * @return $this
     */
    public function html($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set the plain text view for the message.
     *
     * @param  string  $textView
     * @param  array  $data
     * @return $this
     */
    public function text($textView, array $data = [])
    {
        $this->textView = $textView;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the view data for the message.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  string|\Illuminate\Contracts\Mail\Attachable|\Illuminate\Mail\Attachment  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        if ($file instanceof Attachable) {
            $file = $file->toMailAttachment();
        }

        if ($file instanceof Attachment) {
            return $file->attachTo($this);
        }

        $this->attachments = collect($this->attachments)
                    ->push(compact('file', 'options'))
                    ->unique('file')
                    ->all();

        return $this;
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function attachFromStorage($path, $name = null, array $options = [])
    {
        return $this->attachFromStorageDisk(null, $path, $name, $options);
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param  string  $disk
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function attachFromStorageDisk($disk, $path, $name = null, array $options = [])
    {
        $this->diskAttachments = collect($this->diskAttachments)->push([
            'disk' => $disk,
            'path' => $path,
            'name' => $name ?? basename($path),
            'options' => $options,
        ])->unique(function ($file) {
            return $file['name'].$file['disk'].$file['path'];
        })->all();

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->rawAttachments = collect($this->rawAttachments)
                ->push(compact('data', 'name', 'options'))
                ->unique(function ($file) {
                    return $file['name'].$file['data'];
                })->all();

        return $this;
    }

    /**
     * Add a tag header to the message when supported by the underlying transport.
     *
     * @param  string  $value
     * @return $this
     */
    public function tag($value)
    {
        array_push($this->tags, $value);

        return $this;
    }

    /**
     * Add a metadata header to the message when supported by the underlying transport.
     *
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function metadata($key, $value)
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Assert that the given text is present in the HTML email body.
     *
     * @param  string  $string
     * @return $this
     */
    public function assertSeeInHtml($string)
    {
        [$html, $text] = $this->renderForAssertions();

        PHPUnit::assertTrue(
            str_contains($html, $string),
            "Did not see expected text [{$string}] within email body."
        );

        return $this;
    }

    /**
     * Assert that the given text is not present in the HTML email body.
     *
     * @param  string  $string
     * @return $this
     */
    public function assertDontSeeInHtml($string)
    {
        [$html, $text] = $this->renderForAssertions();

        PHPUnit::assertFalse(
            str_contains($html, $string),
            "Saw unexpected text [{$string}] within email body."
        );

        return $this;
    }

    /**
     * Assert that the given text strings are present in order in the HTML email body.
     *
     * @param  array  $strings
     * @return $this
     */
    public function assertSeeInOrderInHtml($strings)
    {
        [$html, $text] = $this->renderForAssertions();

        PHPUnit::assertThat($strings, new SeeInOrder($html));

        return $this;
    }

    /**
     * Assert that the given text is present in the plain-text email body.
     *
     * @param  string  $string
     * @return $this
     */
    public function assertSeeInText($string)
    {
        [$html, $text] = $this->renderForAssertions();

        PHPUnit::assertTrue(
            str_contains($text, $string),
            "Did not see expected text [{$string}] within text email body."
        );

        return $this;
    }

    /**
     * Assert that the given text is not present in the plain-text email body.
     *
     * @param  string  $string
     * @return $this
     */
    public function assertDontSeeInText($string)
    {
        [$html, $text] = $this->renderForAssertions();

        PHPUnit::assertFalse(
            str_contains($text, $string),
            "Saw unexpected text [{$string}] within text email body."
        );

        return $this;
    }

    /**
     * Assert that the given text strings are present in order in the plain-text email body.
     *
     * @param  array  $strings
     * @return $this
     */
    public function assertSeeInOrderInText($strings)
    {
        [$html, $text] = $this->renderForAssertions();

        PHPUnit::assertThat($strings, new SeeInOrder($text));

        return $this;
    }

    /**
     * Render the HTML and plain-text version of the mailable into views for assertions.
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function renderForAssertions()
    {
        if ($this->assertionableRenderStrings) {
            return $this->assertionableRenderStrings;
        }

        return $this->assertionableRenderStrings = $this->withLocale($this->locale, function () {
            Container::getInstance()->call([$this, 'build']);

            $html = Container::getInstance()->make('mailer')->render(
                $view = $this->buildView(), $this->buildViewData()
            );

            if (is_array($view) && isset($view[1])) {
                $text = $view[1];
            }

            $text ??= $view['text'] ?? '';

            if (! empty($text) && ! $text instanceof Htmlable) {
                $text = Container::getInstance()->make('mailer')->render(
                    $text, $this->buildViewData()
                );
            }

            return [(string) $html, (string) $text];
        });
    }

    /**
     * Set the name of the mailer that should send the message.
     *
     * @param  string  $mailer
     * @return $this
     */
    public function mailer($mailer)
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * Register a callback to be called with the Symfony message instance.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withSymfonyMessage($callback)
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called while building the view data.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function buildViewDataUsing(callable $callback)
    {
        static::$viewDataCallback = $callback;
    }

    /**
     * Dynamically bind parameters to the message.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (str_starts_with($method, 'with')) {
            return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
        }

        static::throwBadMethodCallException($method);
    }
}
