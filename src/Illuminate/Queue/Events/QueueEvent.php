<?php

namespace Illuminate\Queue\Events;

use SplObjectStorage;
use SplObserver;
use SplSubject;

abstract class QueueEvent implements SplSubject
{
    protected SplObjectStorage $observers;

    public function __construct()
    {
        $this->observers = new SplObjectStorage;
    }

    public function attach(SplObserver $observer): void
    {
        $this->observers->attach($observer);
    }

    public function detach(SplObserver $observer): void
    {
        $this->observers->detach($observer);
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
}
