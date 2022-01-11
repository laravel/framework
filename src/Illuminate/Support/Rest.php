<?php


namespace Illuminate\Support;

use Illuminate\Contracts\Support\Restable;
use InvalidArgumentException;

class Rest implements Restable
{
    /**
     * @var  int  $seconds
     * @var  int  $milliseconds
     * @var  int  $microseconds
     * @return  void
     */
    public function for(int $seconds = 0, $milliseconds = 0, $microseconds = 0): void
    {
        $this->forSeconds($seconds);
        $this->forMilliseconds($milliseconds);
        $this->forMicroseconds($microseconds);
    }

    /**
     * @var  int  $seconds
     * @return  void
     */
    public function forSeconds(int $seconds = 0): void
    {
        sleep($seconds);
    }


    /**
     * @var  int  $milliseconds
     * @return  void
     */
    public function forMilliseconds(int $milliseconds = 0): void
    {
        msleep($milliseconds);
    }

    /**
     * @var  int  $microseconds
     * @return  void
     */
    public function forMicroseconds(int $microseconds = 0): void
    {
        usleep($microseconds);
    }

    /**
     *
     * @var  float|int|Carbon  $until
     */
    public function until($until): void
    {
        if ($until instanceof Carbon) {
            time_sleep_until($until->timestamp);
        } elseif (is_int($until) || is_float($until)) {
            time_sleep_until($until);
        } else {
            throw new InvalidArgumentException('Invalid $until paramter. Must be instance of Carbon or float');
        }
    }
}
