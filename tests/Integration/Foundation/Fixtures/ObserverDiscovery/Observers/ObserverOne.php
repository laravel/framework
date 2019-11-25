<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers;

use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Models\ModelOne;

class ObserverOne
{
    public function saving(ModelOne $model)
    {
        //
    }
}
