<?php

namespace Illuminate\Foundation\Http\Exceptions;

use Exception;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MaintenanceModeException extends ServiceUnavailableHttpException
{
    /**
     * The timestamp corresponding to the moment when the application was put in maintenance mode.
     *
     * @var int
     */
    protected $time;

    /**
     * The retry in seconds.
     *
     * @var int
     */
    protected $retry;

    /**
     * Constructor.
     *
     * @param  int  $time
     * @param  int  $retryAfter
     * @param  string  $message
     * @param  \Exception  $previous
     * @param  int  $code
     * @return void
     */
    public function __construct($time, $retryAfter = null, $message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct($retryAfter, $message, $previous, $code);
        $this->setTime($time);
        $this->setRetry($retryAfter);
    }

    /**
     * Set the time property.
     *
     * @param  int  $time
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get the time property.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set the retry.
     *
     * @param  int  $retry
     * @return $this
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;

        return $this;
    }

    /**
     * Get the retry property.
     *
     * @return int
     */
    public function getRetry()
    {
        return $this->retry;
    }

    /**
     * Get the planned availability if the retry is set or false otherwise.
     *
     * @return \Carbon\Carbon|bool
     */
    public function getAvailability()
    {
        if ($this->retry !== null) {
            return Carbon::createFromTimestampUTC($this->time)->addSeconds($this->retry);
        }

        return false;
    }
}
