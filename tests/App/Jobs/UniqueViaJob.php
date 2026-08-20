<?php

namespace Illuminate\Tests\App\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;

class UniqueViaJob extends UniqueTestJob
{
    public function uniqueVia(): Cache
    {
        return Container::getInstance()->make(Cache::class);
    }
}
