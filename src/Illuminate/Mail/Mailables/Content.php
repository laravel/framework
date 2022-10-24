<?php

namespace Illuminate\Mail\Mailables;

use Illuminate\Support\Traits\Conditionable;

class Content
{
    use Conditionable;

    /**
     * The Blade view that should be rendered for the mailable.
     *
     * @var string|null
     */
    public $view;

    /**
     * The Blade view that should be rendered for the mailable.
     *
     * Alternative syntax for "view".
     *
     * @var string|null
     */
    public $html;

    /**
     * The Blade view that represents the text version of the message.
     *
     * @var string|null
     */
    public $text;

    /**
     * The Blade view that represents the Markdown version of the message.
     *
     * @var string|null
     */
    public $markdown;

    /**
     * The raw html of the message
     *
     * @var string|null
     */
    public $raw;

    /**
     * The message's view data.
     *
     * @var array
     */
    public $with;

    /**
     * Create a new content definition.
     *
     * @param  string|null  $view
     * @param  string|null  $html
     * @param  string|null  $text
     * @param  string|null  $markdown
     * @param  string|null  $raw
     * @param  array  $with
     *
     * @named-arguments-supported
     */
    public function __construct(string $view = null, string $html = null, string $text = null, string $markdown = null, string $raw = null, array $with = [])
    {
        $this->view = $view;
        $this->html = $html;
        $this->text = $text;
        $this->markdown = $markdown;
        $this->raw = $raw;
        $this->with = $with;
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
     * Set the raw html for the message.
     *
     * @param  string  $html
     * @return $this
     */
    public function raw(string $html)
    {
        $this->raw = $html;

        return $this;
    }

    /**
     * Add a piece of view data to the message.
     *
     * @param  string  $key
     * @param  mixed|null  $value
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
