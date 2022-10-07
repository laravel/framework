<?php

namespace Illuminate\Mail\Mailables;

use Illuminate\Support\Str;

class Headers
{
    /**
     * The message's message ID.
     *
     * @var string|null
     */
    public $messageId;

    /**
     * The message IDs that are referenced by the message.
     *
     * @var array
     */
    public $references;

    /**
     * The message's text headers.
     *
     * @var array
     */
    public $text;

    /**
     * Create a new instance of headers for a message.
     *
     * @param  string|null  $messageId
     * @param  array  $references
     * @param  array  $text
     * @return void
     *
     * @named-arguments-supported
     */
    public function __construct(string $messageId = null, array $references = [], array $text = [])
    {
        $this->messageId = $messageId;
        $this->references = $references;
        $this->text = $text;
    }

    /**
     * Get the references header as a string.
     *
     * @return string
     */
    public function referencesString(): string
    {
        return collect($this->references)->map(function ($messageId) {
            return Str::finish(Str::start($messageId, '<'), '>');
        })->implode(' ');
    }
}
