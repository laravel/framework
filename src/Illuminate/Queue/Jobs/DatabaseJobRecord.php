<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\InteractsWithTime;

class DatabaseJobRecord
{
    use InteractsWithTime;

    /**
     * The underlying job record.
     *
     * @var \stdClass
     */
    protected $record;

    /**
     * Create a new job record instance.
     *
     * @param  \stdClass  $record
     */
    public function __construct($record)
    {
        $this->record = $record;
    }

    /**
     * Increment the number of times the job has been attempted.
     *
     * @return int
     */
    public function increment()
    {
        $this->record->attempts++;

        return $this->record->attempts;
    }

    /**
     * Update the "reserved at" timestamp of the job.
     *
     * @param  int  $offset
     * @return int
     */
    public function touch($offset = 0)
    {
        $this->record->reserved_at = min($this->currentTime() + $offset, 2147483647);

        return $this->record->reserved_at;
    }

    /**
     * Dynamically access the underlying job information.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->record->{$key};
    }
}
