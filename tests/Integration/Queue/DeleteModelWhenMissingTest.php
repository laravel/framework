<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Jobs\DeleteMissingModelJob;
use Illuminate\Tests\App\Models\DeletableModel;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class DeleteModelWhenMissingTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('queue.default', 'database');
        $this->driver = 'database';
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('delete_model_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('delete_model_test_models');
    }

    protected function tearDown(): void
    {
        DeleteMissingModelJob::$handled = false;

        parent::tearDown();
    }

    public function test_deleteModelWhenMissing_and_display_name(): void
    {
        $model = DeletableModel::query()->create(['name' => 'test']);

        DeleteMissingModelJob::dispatch($model);

        DeletableModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertFalse(DeleteMissingModelJob::$handled);
        $this->assertNull(\DB::table('failed_jobs')->first());
    }
}
