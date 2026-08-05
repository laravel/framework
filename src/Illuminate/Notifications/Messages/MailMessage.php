<?php

namespace Illuminate\Notifications\Messages;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Conditionable;

class MailMessage extends SimpleMessage implements Renderable
{
    use Conditionable;

    /**
     * The view to be rendered.
     *
     * @var array|string
     */
    public $view;

    /**
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The Markdown template to render (if applicable).
     *
     * @var string|null
     */
    public $markdown = 'notifications::email';

    /**
     * The current theme being used when generating emails.
     *
     * @var string|null
     */
    public $theme;

    /**
     * The "from" information for the message.
     *
     * @var array
     */
    public $from = [];

    /**
     * The "reply to" information for the message.
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * The "cc" information for the message.
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "bcc" information for the message.
     *
     * @var array
     */
    public $bcc = [];

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
     * The tags for the message.
     *
     * @var array
     */
    public $tags = [];

    /**
     * The metadata for the message.
     *
     * @var array
     */
    public $metadata = [];

    /**
     * Priority level of the message.
     *
     * @var int
     */
    public $priority;

    /**
     * The callbacks for the message.
     *
     * @var array
     */
    public $callbacks = [];

    /**
     * Set the view for the mail message.
     *
     * @param  array|string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = $data;

        $this->markdown = null;

        return $this;
    }

    /**
     * Set the plain text view for the mail message.
     *
     * @param  string  $textView
     * @param  array  $data
     * @return $this
     */
    public function text($textView, array $data = [])
    {
        return $this->view([
            'html' => is_array($this->view) ? ($this->view['html'] ?? null) : $this->view,
            'text' => $textView,
        ], $data);
    }

    /**
     * Set the Markdown template for the notification.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function markdown($view, array $data = [])
    {
        $this->markdown = $view;
        $this->viewData = $data;

        $this->view = null;

        return $this;
    }

    /**
     * Set the default markdown template.
     *
     * @param  string  $template
     * @return $this
     */
    public function template($template)
    {
        $this->markdown = $template;

        return $this;
    }

    /**
     * Set the theme to use with the Markdown template.
     *
     * @param  string  $theme
     * @return $this
     */
    public function theme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Add an image to the notification.
     *
     * The image may be a path, an attachment, or an attachable instance. For
     * in-memory data, use the imageFromData method.
     *
     * @param  string|\Illuminate\Contracts\Mail\Attachable|\Illuminate\Mail\Attachment  $image
     * @param  string|null  $alt
     * @param  int|string|null  $width
     * @param  int|string|null  $height
     * @param  string|null  $style
     * @return $this
     */
    public function image($image, $alt = '', $width = null, $height = null, $style = null)
    {
        return $this->addInlineImageLine(new InlineImage($image, $alt, $width, $height, $style));
    }

    /**
     * Add in-memory data as an inline image to the notification.
     *
     * @param  string|resource|\Closure  $data
     * @param  string  $name
     * @param  string|null  $alt
     * @param  string|null  $mime
     * @param  int|string|null  $width
     * @param  int|string|null  $height
     * @param  string|null  $style
     * @return $this
     */
    public function imageFromData($data, $name, $alt = '', $mime = null, $width = null, $height = null, $style = null)
    {
        $attachment = Attachment::fromData(
            $data instanceof Closure ? $data : fn () => $data,
            $name,
        );

        if (! is_null($mime)) {
            $attachment->withMime($mime);
        }

        return $this->image($attachment, $alt, $width, $height, $style);
    }

    /**
     * Add an inline image from storage to the notification.
     *
     * @param  string  $path
     * @param  string|null  $alt
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function imageFromStorage($path, $alt = '', $name = null, array $options = [])
    {
        return $this->imageFromStorageDisk(null, $path, $alt, $name, $options);
    }

    /**
     * Add an inline image from storage to the notification.
     *
     * @param  string|null  $disk
     * @param  string  $path
     * @param  string|null  $alt
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function imageFromStorageDisk($disk, $path, $alt = '', $name = null, array $options = [])
    {
        $attachment = Attachment::fromStorageDisk($disk, $path);

        if (! is_null($name)) {
            $attachment->as($name);
        }

        if (isset($options['mime'])) {
            $attachment->withMime($options['mime']);
        }

        return $this->image(
            $attachment,
            $alt,
            $options['width'] ?? null,
            $options['height'] ?? null,
            $options['style'] ?? null,
        );
    }

    /**
     * Add a line of text before an image.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string|array|null  $line
     * @param  string|\Illuminate\Contracts\Mail\Attachable|\Illuminate\Mail\Attachment  $image
     * @param  string|null  $alt
     * @param  int|string|null  $width
     * @param  int|string|null  $height
     * @param  string|null  $style
     * @return $this
     */
    public function lineBeforeImage($line, $image, $alt = '', $width = null, $height = null, $style = null)
    {
        return $this->addInlineImageLine(new InlineImageLine(
            new InlineImage($image, $alt, $width, $height, $style),
            $this->formatLine($line),
        ));
    }

    /**
     * Add a line of text after an image.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string|array|null  $line
     * @param  string|\Illuminate\Contracts\Mail\Attachable|\Illuminate\Mail\Attachment  $image
     * @param  string|null  $alt
     * @param  int|string|null  $width
     * @param  int|string|null  $height
     * @param  string|null  $style
     * @return $this
     */
    public function lineAfterImage($line, $image, $alt = '', $width = null, $height = null, $style = null)
    {
        return $this->addInlineImageLine(new InlineImageLine(
            new InlineImage($image, $alt, $width, $height, $style),
            null,
            $this->formatLine($line),
        ));
    }

    /**
     * Add a structured inline image line to the notification.
     *
     * @param  \Illuminate\Notifications\Messages\InlineImage|\Illuminate\Notifications\Messages\InlineImageLine  $line
     * @return $this
     */
    protected function addInlineImageLine($line)
    {
        if (! $this->actionText) {
            $this->introLines[] = $line;
        } else {
            $this->outroLines[] = $line;
        }

        return $this;
    }

    /**
     * Set the from address for the mail message.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->from = [$address, $name];

        return $this;
    }

    /**
     * Set the "reply to" address of the message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        if ($this->arrayOfAddresses($address)) {
            $this->replyTo = array_merge($this->replyTo, $this->parseAddresses($address));
        } else {
            $this->replyTo[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Set the cc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        if ($this->arrayOfAddresses($address)) {
            $this->cc = array_merge($this->cc, $this->parseAddresses($address));
        } else {
            $this->cc[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Set the bcc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        if ($this->arrayOfAddresses($address)) {
            $this->bcc = array_merge($this->bcc, $this->parseAddresses($address));
        } else {
            $this->bcc[] = [$address, $name];
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

        $this->attachments[] = ['file' => $file, 'options' => $options];

        return $this;
    }

    /**
     * Attach multiple files to the message.
     *
     * @param  array<string|\Illuminate\Contracts\Mail\Attachable|\Illuminate\Mail\Attachment|array>  $files
     * @return $this
     */
    public function attachMany($files)
    {
        foreach ($files as $file => $options) {
            if (is_int($file)) {
                $this->attach($options);
            } else {
                $this->attach($file, $options);
            }
        }

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
        $this->rawAttachments[] = ['data' => $data, 'name' => $name, 'options' => $options];

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
     * @param  string|null  $disk
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return $this
     */
    public function attachFromStorageDisk($disk, $path, $name = null, array $options = [])
    {
        $attachment = Attachment::fromStorageDisk($disk, $path);

        if (! is_null($name)) {
            $attachment->as($name);
        }

        if (isset($options['mime'])) {
            $attachment->withMime($options['mime']);
        }

        return $this->attach($attachment);
    }

    /**
     * Add a tag header to the message when supported by the underlying transport.
     *
     * @param  string  $value
     * @return $this
     */
    public function tag($value)
    {
        $this->tags[] = $value;

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
     * Set the priority of this message.
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->priority = $level;

        return $this;
    }

    /**
     * Get the data array for the mail message.
     *
     * @return array
     */
    public function data()
    {
        return array_merge($this->toArray(), $this->viewData);
    }

    /**
     * Parse the multi-address array into the necessary format.
     *
     * @param  array  $value
     * @return array
     */
    protected function parseAddresses($value)
    {
        return (new Collection($value))
            ->map(fn ($address, $name) => [$address, is_numeric($name) ? null : $name])
            ->values()
            ->all();
    }

    /**
     * Determine if the given "address" is actually an array of addresses.
     *
     * @param  mixed  $address
     * @return bool
     */
    protected function arrayOfAddresses($address)
    {
        return is_iterable($address) || $address instanceof Arrayable;
    }

    /**
     * Render the mail notification message into an HTML string.
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function render()
    {
        if (isset($this->view)) {
            return Container::getInstance()->make('mailer')->render(
                $this->view, $this->data()
            );
        }

        $markdown = Container::getInstance()->make(Markdown::class);

        return new HtmlString(
            Container::getInstance()->make('mailer')->render(
                fn ($data) => $markdown->theme($this->theme ?: $markdown->getTheme())
                    ->render(
                        $this->markdown,
                        InlineImageRenderer::render(
                            array_merge($data, $this->data()),
                            $data['message'] ?? null,
                        ),
                    ),
                $this->data(),
            )
        );
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
}
