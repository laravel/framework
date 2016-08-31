<?php

namespace Illuminate\Validation\Rules\Traits;

use DateTimeInterface;
use DateTime;
use Exception;

trait DateValidation
{
    /**
     * Validate the date is after a given date with a given format.
     *
     * @param  string  $format
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    protected function validateWithFormat($format, $value, $otherValue)
    {
        return $this->checkDateTimeOrder($format, $otherValue, $value);
    }

    /**
     * Get the date format for an attribute if it has one.
     *
     * @param  string  $attribute
     * @param \Illuminate\Validation\Validator $valdiator
     * @return string|null
     */
    protected function getDateFormat($attribute, $validator)
    {
        if ($validator->getRules($attribute)->has('date_format')) {
            $params = $validator->getRuleParameters('date_format', $attribute);

            return $params['format'];
        }
    }

    /**
     * Get the date timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function getDateTimestamp($value)
    {
        return $value instanceof DateTimeInterface ? $value->getTimestamp() : strtotime($value);
    }

    /**
     * Given two date/time strings, check that one is after the other.
     *
     * @param  string  $format
     * @param  string  $before
     * @param  string  $after
     * @return bool
     */
    protected function checkDateTimeOrder($format, $before, $after)
    {
        $before = $this->getDateTimeWithOptionalFormat($format, $before);

        $after = $this->getDateTimeWithOptionalFormat($format, $after);

        return ($before && $after) && ($after > $before);
    }

    /**
     * Get a DateTime instance from a string.
     *
     * @param  string  $format
     * @param  string  $value
     * @return \DateTime|null
     */
    protected function getDateTimeWithOptionalFormat($format, $value)
    {
        $date = DateTime::createFromFormat($format, $value);

        if ($date) {
            return $date;
        }

        try {
            return new DateTime($value);
        } catch (Exception $e) {
            //
        }
    }
}
