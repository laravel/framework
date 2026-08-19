<?php

namespace Illuminate\Tests\App\Models\Events;

trait CustomEventTrait
{
    public function completeCustomAction()
    {
        $this->custom_attribute = true;

        $this->fireModelEvent('customEvent');
    }

    public function initializeCustomEventTrait()
    {
        $this->addObservableEvents([
            'customEvent',
        ]);
    }
}
