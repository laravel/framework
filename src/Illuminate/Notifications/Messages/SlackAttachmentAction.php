<?php

namespace Illuminate\Notifications\Messages;

use Closure;

class SlackAttachmentAction
{
    /**
     * The name field of the attachment action.
     *
     * @var string
     */
    protected $name;

    /**
     * The content of the attachment action.
     *
     * @var string
     */
    protected $content;

    /**
     * The type of the attachment action.
     *
     * @var string
     */
    protected $type = 'button';

    /**
     * The value of the attachment action.
     *
     * @var string
     */
    protected $value;

    /**
     * The action's confirmation fields.
     *
     * @var array
     */
    protected $confirmationFields;

    /**
     * The style of the attachment action.
     *
     * @var string
     */
    protected $style = 'default';

    /**
     * The options of the attachment action.
     *
     * @var array
     */
    protected $options;

    /**
     * The option groups of the attachment action.
     *
     * @var array
     */
    protected $optionGroups;

    /**
     * The minimum query length of the attachment action.
     *
     * @var int
     */
    protected $minQueryLength = 1;

    /**
     * The data source of the attachment action.
     *
     * @var string
     */
    protected $dataSource = 'static';

    /**
     * Set the name of the field.
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
     * Set the content of the field.
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
     * Set the type of the field.
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
     * Set the value of the field.
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
     * Set the style of the field.
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
     * Set the options of the field.
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
     * Set the options of the field.
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
     * Set the minimum query length of the field.
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
     * Set the data source of the field.
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
     * Get the array representation of the attachment field.
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
