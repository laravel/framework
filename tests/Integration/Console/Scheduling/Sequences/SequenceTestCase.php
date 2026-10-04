<?php

namespace Illuminate\Tests\Integration\Console\Scheduling\Sequences;

use Orchestra\Testbench\TestCase;

abstract class SequenceTestCase extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('scheduling', require __DIR__.'/../../../../../config/scheduling.php');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('queue.connections.database', [
            'driver' => 'database', 'connection' => null, 'table' => 'jobs',
            'queue' => 'default', 'retry_after' => 90,
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        \Illuminate\Support\Facades\Schema::create('sequence_entities', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('jobs', function ($table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        (require __DIR__.'/../../../../../src/Illuminate/Console/Scheduling/Sequences/Console/stubs/sequences.stub')->up();
    }

}
