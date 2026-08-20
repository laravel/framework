<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\DeleteNotificationModel;
use Illuminate\Tests\App\Notifications\DeleteWhenMissingNotification;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class DeleteNotificationWhenMissingModelTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('queue.default', 'database');
        $this->driver = 'database';
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('delete_notification_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('delete_notification_test_models');
    }

    protected function tearDown(): void
    {
        DeleteWhenMissingNotification::$sent = false;

        parent::tearDown();
    }

    public function test_deleteModelWhenMissing_on_queued_notification(): void
    {
        $model = DeleteNotificationModel::query()->create(['name' => 'test']);

        NotificationFacade::send($model, new DeleteWhenMissingNotification($model));

        DeleteNotificationModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertFalse(DeleteWhenMissingNotification::$sent);
        $this->assertNull(\DB::table('failed_jobs')->first());
    }
}
