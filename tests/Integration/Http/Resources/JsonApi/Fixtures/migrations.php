<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->index();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});

Schema::create('profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique();
    $table->date('date_of_birth')->nullable();
    $table->string('timezone')->nullable();
});

Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->index();
    $table->string('name');
    $table->boolean('personal_team');
});

Schema::create('team_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id');
    $table->foreignId('user_id');
    $table->string('role')->nullable();
    $table->timestamps();

    $table->index(['team_id', 'user_id']);
});

Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->unique();
    $table->foreignId('user_id')->index()->nullable();
    $table->text('content');
    $table->timestamps();
});
