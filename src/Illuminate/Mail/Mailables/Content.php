<?php

namespace Illuminate\Mail\Mailables;

class Content
{
    public $view;
    public $text;
    public $markdown;
    public $with;

    public function __construct(string $view = null, string $text = null, $markdown = null, array $with = [])
    {
        $this->view = $view;
        $this->text = $text;
        $this->markdown = $markdown;
        $this->with = $with;
    }
}
