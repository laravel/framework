<?php

namespace Illuminate\Foundation\Http\Exceptions;

use Exception;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MaintenanceModeException extends ServiceUnavailableHttpException
{
    /**
     * When the application was put in maintenance mode.
     *
     * @var int
     */
    public $wentDownAt;

    /**
     * The number of seconds to wait before retrying.
     *
     * @var \Carbon\Carbon
     */
    public $retryAfter;

    /**
     * When the application should next be available.
     *
     * @var \Carbon\Carbon
     */
    public $willBeAvailableAt;

    /**
     * Create a new exception instance.
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

        $this->wentDownAt = Carbon::createFromTimestamp($time);

        if ($retryAfter) {
            $this->retryAfter = $retryAfter;

            $this->willBeAvailableAt = $this->wentDownAt->addSeconds($this->retryAfter);
        }
    }
}
