<?php

namespace Illuminate\Session;

use Illuminate\Contracts\Queue\ShouldQueue;
use SessionHandlerInterface;

class CollectGarbageJob implements ShouldQueue
{
    /**
     * The session handler instance for which the garbage collection should run.
     *
     * @var \SessionHandlerInterface
     */
    protected $sessionHandler;

    /**
     * Session lifetime in seconds.
     *
     * @var int
     */
    protected $sessionLifetime;

    /**
     * Create a new job instance.
     *
     * @param  \SessionHandlerInterface  $sessionHandler
     * @param  int  $sessionLifetime
     * @return void
     */
    public function __construct(SessionHandlerInterface $sessionHandler, $sessionLifetime)
    {
        $this->sessionHandler = $sessionHandler;
        $this->sessionLifetime = $sessionLifetime;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->sessionHandler->gc($this->sessionLifetime);
    }
}
