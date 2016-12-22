<?php

namespace Illuminate\Foundation\Testing\Constraints\Mail;

use Illuminate\Foundation\Testing\Constraints\HasLink;

class HtmlLink
{
    /**
     * @var string
     */
    private $text;
    /**
     * @var string
     */
    private $url;

    public function __construct($text, $url = null)
    {
        $this->text = $text;
        $this->url = $url;
    }

    public function matches(\Swift_Message $message)
    {
        return (new HasLink($this->text, $this->url))->matches($message->getBody());
    }

    public function __toString()
    {
        return "HTML link with text: [{$this->text}]"
            .($this->url ? " and URL [{$this->url}]" : '');
    }
}
