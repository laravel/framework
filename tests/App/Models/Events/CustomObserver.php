<?php

namespace Illuminate\Tests\App\Models\Events;

class CustomObserver
{
    public function customEvent(EloquentModelStubWithCustomEventFromTrait $model)
    {
        $model->observer_attribute = true;
    }
}
