<?php

namespace Illuminate\Mail\Mailables;

use Illuminate\Support\Traits\Conditionable;

class Content
{
    use Conditionable;

    /**
     * Create a new content definition.
     *
     * @named-arguments-supported
     */
    public function __construct(
        public ?string $view = null,
        public ?string $html = null,
        public ?string $text = null,
        public ?string $markdown = null,
        public array $with = [],
        public ?string $htmlString = null,
    ) {
    }

    /**
     * Set the view for the message.
     *
     * @param  string  $view
     * @return $this
     */
    public function view(string $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set the view for the message.
     *
     * @param  string  $view
     * @return $this
     */
    public function html(string $view)
    {
        return $this->view($view);
    }

    /**
     * Set the plain text view for the message.
     *
     * @param  string  $view
     * @return $this
     */
    public function text(string $view)
    {
        $this->text = $view;

        return $this;
    }

    /**
     * Set the Markdown view for the message.
     *
     * @param  string  $view
     * @return $this
     */
    public function markdown(string $view)
    {
        $this->markdown = $view;

        return $this;
    }

    /**
     * Set the pre-rendered HTML for the message.
     *
     * @param  string  $html
     * @return $this
     */
    public function htmlString(string $html)
    {
        $this->htmlString = $html;

        return $this;
    }

    /**
     * Add a piece of view data to the message.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->with = array_merge($this->with, $key);
        } else {
            $this->with[$key] = $value;
        }

        return $this;
    }
}
