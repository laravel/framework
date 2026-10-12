<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('cache.default', 'array')]
class BufferedStoreTest extends TestCase
{
    public function test_it_buffers_the_given_driver()
    {
        Cache::put('name', 'Tim', 60);

        $this->assertSame('Tim', Cache::buffered()->get('name'));

        Cache::put('name', 'Taylor', 60);

        $this->assertSame('Taylor', Cache::get('name'));
        $this->assertSame('Tim', Cache::buffered()->get('name'));
    }

    public function test_it_resolves_one_buffer_per_driver()
    {
        $this->assertSame(Cache::buffered(), Cache::buffered('array'));
        $this->assertSame(Cache::buffered(), Cache::buffered('array', 10));
    }

    public function test_it_resets_buffer_with_scoped_instances()
    {
        Cache::put('name', 'Tim', 60);
        Cache::buffered()->get('name');
        Cache::put('name', 'Taylor', 60);

        $this->app->forgetScopedInstances();

        $this->assertSame('Taylor', Cache::buffered()->get('name'));
    }

    public function test_it_supports_tags()
    {
        Cache::tags('people')->put('name', 'Tim', 60);

        $this->assertSame('Tim', Cache::buffered()->tags('people')->get('name'));

        Cache::tags('people')->put('name', 'Taylor', 60);

        $this->assertSame('Tim', Cache::buffered()->tags('people')->get('name'));

        Cache::buffered()->tags('people')->flush();

        $this->assertNull(Cache::tags('people')->get('name'));
        $this->assertNull(Cache::buffered()->tags('people')->get('name'));
    }
}
