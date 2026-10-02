<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('cache.default', 'file')]
class FileCacheFunnelTest extends CacheFunnelTestCase
{
    protected function cache(): Repository
    {
        return Cache::store('file');
    }
}
