<?php

namespace Illuminate\Mail\Mailables;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

class Headers
{
    use Conditionable;

    /**
     * Create a new instance of headers for a message.
     *
     * @named-arguments-supported
     */
    public function __construct(
        public ?string $messageId = null,
        public array $references = [],
        public array $text = [],
    ) {
    }

    /**
     * Set the message ID.
     *
     * @param  string  $messageId
     * @return $this
     */
    public function messageId(string $messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Set the message IDs referenced by this message.
     *
     * @param  array  $references
     * @return $this
     */
    public function references(array $references)
    {
        $this->references = array_merge($this->references, $references);

        return $this;
    }

    /**
     * Set the headers for this message.
     *
     * @param  array  $text
     * @return $this
     */
    public function text(array $text)
    {
        $this->text = array_merge($this->text, $text);

        return $this;
    }

    /**
     * Get the references header as a string.
     *
     * @return string
     */
    public function referencesString(): string
    {
        return (new Collection($this->references))
            ->map(fn ($messageId) => Str::of($messageId)->start('<')->finish('>')->value())
            ->implode(' ');
    }
}
