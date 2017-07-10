<?php

namespace Illuminate\Notifications\Messages;

class SlackActionOption
{
    /**
     * The text displayed for the option.
     *
     * @var string
     */
    protected $text;

    /**
     * The value for the option.
     *
     * @var string
     */
    protected $value;

    /**
     * Set the title of the action option.
     *
     * @param  string  $text
     * @return $this
     */
    public function text($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set the value of the action option.
     *
     * @param  string  $value
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the array representation of the action option.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'text' => $this->text,
            'value' => $this->value,
        ];
    }
}
