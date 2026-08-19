<?php

namespace Illuminate\Tests\Integration\Database\EloquentPivotWithoutTimestampTest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function migrate()
{
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('role_user', function (Blueprint $table) {
        $table->foreignId('user_id');
        $table->foreignId('role_id');
        $table->text('notes');
        $table->timestamp('created_at')->nullable();
    });
}
