<?php

namespace Illuminate\Mail;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;

class Attachment
{
    use Macroable;

    /**
     * The attached file's filename.
     *
     * @var string|null
     */
    public $as;

    /**
     * The attached file's mime type.
     *
     * @var string|null
     */
    public $mime;

    /**
     * A callback that attaches the attachment to the mail message.
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * Create a mail attachment.
     *
     * @param  \Closure  $resolver
     * @return void
     */
    private function __construct(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Create a mail attachment from a path.
     *
     * @param  string  $path
     * @return static
     */
    public static function fromPath($path)
    {
        return new static(fn ($attachment, $pathStrategy) => $pathStrategy($path, $attachment));
    }

    /**
     * Create a mail attachment from a URL.
     *
     * @param  string  $url
     * @return static
     */
    public static function fromUrl($url)
    {
        return static::fromPath($url);
    }

    /**
     * Create a mail attachment from in-memory data.
     *
     * @param  \Closure  $data
     * @param  string|null  $name
     * @return static
     */
    public static function fromData(Closure $data, $name = null)
    {
        return (new static(
            fn ($attachment, $pathStrategy, $dataStrategy) => $dataStrategy($data, $attachment)
        ))->as($name);
    }

    /**
     * Create a mail attachment from a file in the default storage disk.
     *
     * @param  string  $path
     * @return static
     */
    public static function fromStorage($path)
    {
        return static::fromStorageDisk(null, $path);
    }

    /**
     * Create a mail attachment from a file in the specified storage disk.
     *
     * @param  string|null  $disk
     * @param  string  $path
     * @return static
     */
    public static function fromStorageDisk($disk, $path)
    {
        return new static(function ($attachment, $pathStrategy, $dataStrategy) use ($disk, $path) {
            $storage = Container::getInstance()->make(
                FilesystemFactory::class
            )->disk($disk);

            $attachment
                ->as($attachment->as ?? basename($path))
                ->withMime($attachment->mime ?? $storage->mimeType($path));

            return $dataStrategy(fn () => $storage->get($path), $attachment);
        });
    }

    /**
     * Set the attached file's filename.
     *
     * @param  string|null  $name
     * @return $this
     */
    public function as($name)
    {
        $this->as = $name;

        return $this;
    }

    /**
     * Set the attached file's mime type.
     *
     * @param  string  $mime
     * @return $this
     */
    public function withMime($mime)
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * Attach the attachment with the given strategies.
     *
     * @param  \Closure  $pathStrategy
     * @param  \Closure  $dataStrategy
     * @return mixed
     */
    public function attachWith(Closure $pathStrategy, Closure $dataStrategy)
    {
        return ($this->resolver)($this, $pathStrategy, $dataStrategy);
    }

    /**
     * Attach the attachment to a built-in mail type.
     *
     * @param  \Illuminate\Mail\Mailable|\Illuminate\Mail\Message|\Illuminate\Notifications\Messages\MailMessage  $mail
     * @param  array  $options
     * @return mixed
     */
    public function attachTo($mail, $options = [])
    {
        return $this->attachWith(
            fn ($path) => $mail->attach($path, [
                'as' => $options['as'] ?? $this->as,
                'mime' => $options['mime'] ?? $this->mime,
            ]),
            function ($data) use ($mail, $options) {
                $options = [
                    'as' => $options['as'] ?? $this->as,
                    'mime' => $options['mime'] ?? $this->mime,
                ];

                if ($options['as'] === null) {
                    throw new RuntimeException('Attachment requires a filename to be specified.');
                }

                return $mail->attachData($data(), $options['as'], ['mime' => $options['mime']]);
            }
        );
    }

    /**
     * Determine if the given attachment is equivalent to this attachment.
     *
     * @param  \Illuminate\Mail\Attachment  $attachment
     * @param  array  $options
     * @return bool
     */
    public function isEquivalent(Attachment $attachment, $options = [])
    {
        return with([
            'as' => $options['as'] ?? $attachment->as,
            'mime' => $options['mime'] ?? $attachment->mime,
        ], fn ($options) => $this->attachWith(
            fn ($path) => [$path, ['as' => $this->as, 'mime' => $this->mime]],
            fn ($data) => [$data(), ['as' => $this->as, 'mime' => $this->mime]],
        ) === $attachment->attachWith(
            fn ($path) => [$path, $options],
            fn ($data) => [$data(), $options],
        ));
    }
}
