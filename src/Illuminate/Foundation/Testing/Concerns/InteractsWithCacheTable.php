<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait InteractsWithCacheTable
{
    /**
     * Create all cache related table tables
     */
    protected function createCacheTables()
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Drop all cache related table tables
     */
    protected function dropCacheTables()
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
}
