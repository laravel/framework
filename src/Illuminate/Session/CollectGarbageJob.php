<?php

namespace Illuminate\Session;

use SessionHandlerInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class CollectGarbageJob implements ShouldQueue
{
    /**
     * The session handler instance for which the garbage collection should run.
     *
     * @var \SessionHandlerInterface
     */
    protected $handler;

    /**
     * Session lifetime in seconds.
     *
     * @var int
     */
    protected $lifetime;

    /**
     * Create a new job instance.
     *
     * @param  \SessionHandlerInterface  $handler
     * @param  int  $lifetime
     * @return void
     */
    public function __construct(SessionHandlerInterface $handler, $lifetime)
    {
        $this->handler = $handler;
        $this->lifetime = $lifetime;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->handler->gc($this->lifetime);
    }
}
