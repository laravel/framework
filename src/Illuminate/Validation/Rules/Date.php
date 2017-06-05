<?php

namespace Illuminate\Validation\Rules;

use DateTimeInterface;
use InvalidArgumentException;

class Date
{
    /** @var string $type The type of the rule to apply. */
    protected $type;

    /** @var string $date The date or field name to compare the type against. */
    protected $date;

    /** @var array allowedTypes The allowed types of the rule. */
    const ALLOWEDTYPES = [
        'date',
        'after',
        'after_or_equal',
        'before',
        'before_or_equal',
    ];

    /**
     * Create a new date rule instance.
     *
     * @param string $type
     * @param string|\DateTimeInterface $date
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct($type = 'date', $date = '')
    {
        $this->type($type);
        $this->date($date);
    }

    /**
     * Set the type of the rule manually.
     *
     * @param string $value
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function type($value)
    {
        if (! in_array($value, self::ALLOWEDTYPES)) {
            throw new InvalidArgumentException();
        }

        $this->type = $value;

        return $this;
    }

    /**
     * Set the date or field to compare the submitted value against.
     *
     * @param string|\DateTimeInterface $value
     * @return $this
     */
    public function date($value)
    {
        if ($value instanceof DateTimeInterface) {
            $this->date = $value->format('Y-m-d');

            return $this;
        }

        $this->date = $value;

        return $this;
    }

    /**
     * Set the type to "after".
     *
     * @return $this
     */
    public function after()
    {
        $this->type('after');

        return $this;
    }

    /**
     * Set the type to "after_or_equal".
     *
     * @return $this
     */
    public function afterOrEqual()
    {
        $this->type('after_or_equal');

        return $this;
    }

    /**
     * Set the type to "before".
     *
     * @return $this
     */
    public function before()
    {
        $this->type('before');

        return $this;
    }

    /**
     * Set the type to "before_or_equal".
     *
     * @return $this
     */
    public function beforeOrEqual()
    {
        $this->type('before_or_equal');

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        $extra = '';
        if ($this->type !== 'date' && $this->date !== '') {
            $extra = '|'.$this->type.':'.$this->date;
        }

        return 'date'.$extra;
    }
}
