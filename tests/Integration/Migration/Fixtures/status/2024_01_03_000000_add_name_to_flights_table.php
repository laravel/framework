<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function shouldRun(): bool
    {
        return Schema::hasTable('flights');
    }

    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
    }
};
