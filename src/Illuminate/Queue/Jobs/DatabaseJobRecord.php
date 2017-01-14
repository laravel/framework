<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Queue\InteractsWithTime;

class DatabaseJobRecord
{
    use InteractsWithTime;

    /**
     * The underlying job record.
     *
     * @var \StdClass
     */
    protected $record;

    /**
     * Create a new job record instance.
     *
     * @param  \StdClass  $record
     * @return void
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
     * @return int
     */
    public function touch()
    {
        $this->record->reserved_at = $this->currentTime();

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
