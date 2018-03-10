<?php

namespace Illuminate\Validation\Rules;

use DateTime;

class DateComparison
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var bool
     */
    protected $orEqual = false;

    /**
     * @var string
     */
    protected $comparison = 'date_equals';

    /**
     * DateComparison constructor.
     * @param  DateTime  $dateTime
     */
    public function __construct(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return $this
     */
    public function before()
    {
        $this->comparison = 'before';

        return $this;
    }

    /**
     * @return $this
     */
    public function after()
    {
        $this->comparison = 'after';

        return $this;
    }

    /**
     * @return $this
     */
    public function orEqual()
    {
        $this->orEqual = true;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s%s:%s',
            $this->comparison,
            $this->orEqual && $this->comparison !== 'date_equals' ? '_or_equal' : '',
            $this->dateTime->format('Y-m-d H:i:s')
        );
    }
}
