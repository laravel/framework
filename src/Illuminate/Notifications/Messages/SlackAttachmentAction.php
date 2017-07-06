<?php

namespace Illuminate\Notifications\Messages;

use Closure;

class SlackAttachmentAction
{
    /**
     * The specific name of the action to be performed.
     *
     * @var string
     */
    protected $name;

    /**
     * The user-facing content for the message button or menu representing this action.
     *
     * @var string
     */
    protected $content;

    /**
     * The type of action. Can be either "button" or "select".
     *
     * @var string
     */
    protected $type = 'button';

    /**
     * The specific identifier for the action.
     *
     * @var string
     */
    protected $value;

    /**
     * The optional confirmation fields to be shown
     * be shown when an action button is clicked.
     *
     * @var array
     */
    protected $confirmationFields;

    /**
     * The style of the attachment action. Useful for
     * emphasizing a primary or dangerous action.
     *
     * @var string
     */
    protected $style = 'default';

    /**
     * The individual options to appear in a message menu.
     *
     * @var array
     */
    protected $options;

    /**
     * An alternate, semi-hierarchal way to list available options in a
     * message menu. This replaces and supersedes the options array.
     *
     * @var array
     */
    protected $optionGroups;

    /**
     * The data source of the attachment's actions.
     * Can be "static", "users", "channels", "conversations", or "external".
     *
     * @var string
     */
    protected $dataSource = 'static';

    /**
     * If present, Slack will wait until the specified number of characters are
     * entered before sending a request to your app's external suggestions
     * API endpoint. Only applies when data_source is set to external.
     *
     * @var int
     */
    protected $minQueryLength = 1;

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
     * Add a confirmation field for the action.
     *
     * @param  \Closure $callback
     *
     * @return $this
     */
    public function confirmationField(Closure $callback)
    {
        $this->confirmationFields[] = $confirmationField = new SlackActionConfirmationField();

        $callback($confirmationField);

        return $this;
    }

    /**
     * Set the confirmation fields of the action.
     *
     * @param  array $confirmationFields
     *
     * @return $this
     */
    public function confirmationFields(array $confirmationFields)
    {
        $this->confirmationFields = $confirmationFields;

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
     * @param  array $options
     *
     * @return $this
     */
    public function options($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the options of the action.
     *
     * @param  array $optionGroups
     *
     * @return $this
     */
    public function optionGroups($optionGroups)
    {
        $this->optionGroups = $optionGroups;

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
        return [
            'name'             => $this->name,
            'text'             => $this->content,
            'type'             => $this->type,
            'value'            => $this->value,
            'confirm'          => $this->confirmationFields,
            'style'            => $this->style,
            'options'          => $this->options,
            'option_groups'    => $this->optionGroups,
            'min_query_length' => $this->minQueryLength,
            'data_source'      => $this->dataSource,
        ];
    }
}
