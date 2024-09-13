<?php

namespace Illuminate\Tests\Integration\Database\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesUniqueUsersUUIDTable
{
    protected function createUniqueUsersUUIDTable(): void
    {
        if (! Schema::hasTable('database_eloquent_integration_unique_users_uuid')) {
            Schema::create('database_eloquent_integration_unique_users_uuid', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamp('birthday', 6)->nullable();
                $table->timestamps();
            });
        }
    }

    protected function dropUniqueUsersUUIDTable(): void
    {
        Schema::dropIfExists('database_eloquent_integration_unique_users_uuid');
    }
}
