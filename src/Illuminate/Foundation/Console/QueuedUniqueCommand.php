<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class QueuedUniqueCommand extends QueuedCommand implements ShouldBeUnique
{
    /**
     * The unique id for this job.
     *
     * @var string
     */
    public $uniqueId = __CLASS__;

    /**
     * The amount of seconds to keep the command unique.
     *
     * @var int
     */
    public $uniqueFor = 0;

    /**
     * Sets the unique id for this command.
     *
     * @param  string  $id
     * @return void
     */
    public function setUniqueId($id)
    {
        $this->uniqueId = $id;
    }

    /**
     * The amount of seconds to keep the command unique.
     *
     * @param  int  $amount
     * @return void
     */
    public function setUniqueFor($amount)
    {
        $this->uniqueFor = $amount;
    }
}
