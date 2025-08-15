<?php

namespace Illuminate\Notifications;

class Line
{
    /**
     * The line content.
     *
     * @var string
     */
    public string $content;

    /**
     * Create a new line.
     *
     * @param  string  $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }
}
