<?php

namespace Illuminate\Mail\Mailables;

use Illuminate\Support\Str;

class Headers
{
    public $messageId;
    public $references;
    public $text;

    public function __construct(string $messageId = null, array $references = [], array $text = [])
    {
        $this->messageId = $messageId;
        $this->references = $references;
        $this->text = $text;
    }

    public function referencesString(): string
    {
        return collect($this->references)->map(function ($messageId) {
            return Str::finish(Str::start($messageId, '<'), '>');
        })->implode(' ');
    }
}
