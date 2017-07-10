<?php

namespace Illuminate\Notifications\Messages;

class SlackAttachmentAction
{
    /**
     * The specific name of the action to be performed.
     *
     * @var string
     */
    public $name;

    /**
     * The user-facing content for the message button or menu representing this action.
     *
     * @var string
     */
    public $content;

    /**
     * The type of action. Can be either "button" or "select".
     *
     * @var string
     */
    public $type = 'button';

    /**
     * The specific identifier for the action.
     *
     * @var string
     */
    public $value;

    /**
     * The optional confirmation window to be shown
     * be shown when an action button is clicked.
     *
     * @var array
     */
    public $confirmation;

    /**
     * The style of the attachment action. Useful for
     * emphasizing a primary or dangerous action.
     *
     * @var string
     */
    public $style = 'default';

    /**
     * The individual options to appear in a message menu.
     *
     * @var array
     */
    public $options;

    /**
     * The data source of the attachment's actions.
     * Can be "static", "users", "channels", "conversations", or "external".
     *
     * @var string
     */
    public $dataSource = 'static';

    /**
     * If present, Slack will wait until the specified number of characters are
     * entered before sending a request to your app's external suggestions
     * API endpoint. Only applies when data_source is set to external.
     *
     * @var int
     */
    public $minQueryLength = 1;

    /**
     * Set the name of the action.
     *
     * @param  string $name
     *
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the content of the action.
     *
     * @param  string $content
     *
     * @return $this
     */
    public function text($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the type of the action.
     *
     * @param  string $type
     *
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the value of the action.
     *
     * @param  string $value
     *
     * @return $this
     */
    public function value($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Add a confirmation window for the action.
     *
     * @param  \Closure|string  $title
     * @param  array  $content
     *
     * @return $this
     */
    public function confirmation($title, $content = [])
    {
        if (is_callable($title)) {
            $callback = $title;

            $callback($confirmation = new SlackActionConfirmationField);

            $this->confirmation = $confirmation;

            return $this;
        }

        $this->confirmation = $content;

        return $this;
    }

    /**
     * Set the style of the action.
     *
     * @param  string $style
     *
     * @return $this
     */
    public function style($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Set the options of the action.
     *
     * @param  \Closure|string  $title
     * @param  array  $content
     *
     * @return $this
     */
    public function option($title, $content = [])
    {
        if (is_callable($title)) {
            $callback = $title;

            $callback($option = new SlackActionOption);

            $this->options[] = $option;

            return $this;
        }

        $this->options[$title] = $content;

        return $this;
    }

    /**
     * Set the options of the action.
     *
     * @param  array  $options
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the data source of the action.
     *
     * @param  string $dataSource
     *
     * @return $this
     */
    public function dataSource($dataSource)
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * Set the minimum query length of the action.
     *
     * @param  int $minQueryLength
     *
     * @return $this
     */
    public function minQueryLength($minQueryLength)
    {
        $this->minQueryLength = $minQueryLength;

        return $this;
    }

    /**
     * Get the array representation of the attachment action.
     *
     * @return array
     */
    public function toArray()
    {
        $action = [
            'name'             => $this->name,
            'text'             => $this->content,
            'type'             => $this->type,
            'value'            => $this->value,
            'style'            => $this->style,
            'options'          => $this->optionsArray(),
            'min_query_length' => $this->minQueryLength,
            'data_source'      => $this->dataSource,
        ];

        // If set to null, Slack will show a generic confirmation window even if we have no data set.
        // Due to this, we only want to set the confirm attribute if we actually want a confirmation.
        if ($confirmation = $this->confirmArray()) {
            $action['confirm'] = $confirmation;
        }

        return $action;
    }

    /**
     * Get the array representation of the attachment action.
     *
     * @return array
     */
    protected function confirmArray()
    {
        if ($this->confirmation instanceof SlackActionConfirmationField) {
            return $this->confirmation->toArray();
        }

        return $this->confirmation;
    }

    /**
     * Format the actions's options.
     *
     * @return array
     */
    protected function optionsArray()
    {
        return collect($this->options)->map(function ($value) {
            if ($value instanceof SlackActionOption) {
                return $value->toArray();
            }

            return $value;
        })->values()->all();
    }
}
