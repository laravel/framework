<?php

namespace Illuminate\Contracts\Support;

use Carbon\Carbon;

interface Restable
{
    /**
     * @var  int  $seconds
     * @var  int  $milliseconds
     * @var  int  $microseconds
     * @return  void
     */
    public function for(int $seconds = 0, int $milliseconds = 0, int $microseconds = 0): void;

    /**
     * @var  int  $seconds
     * @return  void
     */
    public function forSeconds(int $seconds = 0): void;

    /**
     * @var  int  $milliseconds
     * @return  void
     */
    public function forMilliseconds(int $milliseconds = 0): void;

    /**
     * @var  int  $microseconds
     * @return  void
     */
    public function forMicroseconds(int $microseconds = 0): void;

    /**
     * Rest until the time specified. Timestamp or Carbon Object.
     * @var  float|int|Carbon  $until
     * @return  void
     */
    public function until($until): void;
}
