<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;

class UniqueJob extends DispatchableJob implements ShouldBeUnique
{
    use InteractsWithQueue;

    public function uniqueId()
    {
        return self::$value;
    }
}
