<?php

namespace Illuminate\Mail\Mailables;

use Illuminate\Contracts\Support\Htmlable;
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
     * @var string
     */
    public $markdown;

    /**
     * The pre-rendered HTML of the message.
     *
     * @var string|null
     */
    public $htmlString;

    /**
     * The message's view data.
     *
     * @var array
     */
    public $with;

    /**
     * Create a new content definition.
     */
    public function __construct(
        ?string $view = null,
        ?string $html = null,
        ?string $text = null,
        ?string $markdown = null,
        array $with = [],
        ?string $htmlString = null,
    ) {
        $this->view = $view;
        $this->html = $html;
        $this->text = $text;
        $this->markdown = $markdown ?? 'mails::email';
        $this->with = array_merge(['outroLines' => [], 'introLines' => []], $with);
        $this->htmlString = $htmlString;
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

    /**
     * Set the greeting of the notification.
     *
     * @param  string  $greeting
     * @return $this
     */
    public function greeting($greeting)
    {
        return $this->with('greeting', $greeting);
    }

    /**
     * Set the salutation of the notification.
     *
     * @param  string  $salutation
     * @return $this
     */
    public function salutation($salutation)
    {
        return $this->with('salutation', $salutation);
    }

    /**
     * Add a line of text to the notification.
     *
     * @param  mixed  $line
     * @return $this
     */
    public function line($line)
    {
        $key = array_key_exists('actionText', $this->with)
            ? 'outroLines'
            : 'introLines';

        return $this->with([$key => array_merge($this->with[$key] ?? [], [$this->formatLine($line)])]);
    }

    /**
     * Add a line of text to the notification if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  mixed  $line
     * @return $this
     */
    public function lineIf($boolean, $line)
    {
        if ($boolean) {
            return $this->line($line);
        }

        return $this;
    }

    /**
     * Add lines of text to the notification.
     *
     * @param  iterable  $lines
     * @return $this
     */
    public function lines($lines)
    {
        foreach ($lines as $line) {
            $this->line($line);
        }

        return $this;
    }

    /**
     * Add lines of text to the notification if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  iterable  $lines
     * @return $this
     */
    public function linesIf($boolean, $lines)
    {
        if ($boolean) {
            return $this->lines($lines);
        }

        return $this;
    }

    /**
     * Format the given line of text.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string|array|null  $line
     * @return \Illuminate\Contracts\Support\Htmlable|string
     */
    protected function formatLine($line)
    {
        if ($line instanceof Htmlable) {
            return $line;
        }

        if (is_array($line)) {
            return implode(' ', array_map('trim', $line));
        }

        return trim(implode(' ', array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $line ?? ''))));
    }

    /**
     * Configure the "call to action" button.
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url, $color = 'primary')
    {
        return $this->with([
            'actionText' => $text,
            'actionUrl' => $url,
            'color' => $color,
            'displayableActionUrl' => str_replace(['mailto:', 'tel:'], '', $url),
        ]);
    }
}
