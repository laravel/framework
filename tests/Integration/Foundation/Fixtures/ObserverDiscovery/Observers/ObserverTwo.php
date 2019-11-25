<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Observers;

use Illuminate\Tests\Integration\Foundation\Fixtures\ObserverDiscovery\Models\ModelTwo;

class ObserverTwo
{
    public function saving(ModelTwo $model)
    {
        //
    }
}
