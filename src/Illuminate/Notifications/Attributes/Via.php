<?php

namespace Illuminate\Notifications\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Via
{
    /**
     * The channels that the notification should be sent on.
     *
     * @var array
     */
    public array $channels;

    /**
     * Create a new attribute instance.
     *
     * @param  string|list<string>  $channels
     */
    public function __construct(string|array $channels)
    {
        $this->channels = is_array($channels) ? $channels : [$channels];
    }
}
