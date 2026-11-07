<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function shouldRun(): bool
    {
        return false;
    }

    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $table) {
            $table->id();
        });
    }
};
