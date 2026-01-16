<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
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

    protected function defineDatabaseMigrations()
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

    #[\Override]
    protected function tearDown(): void
    {
        DeleteMissingModelNotification::$handled = false;

        parent::tearDown();
    }

    public function test_deleteWhenMissingModels_works_with_queued_notifications(): void
    {
        $model = NotificationTestModel::query()->create(['name' => 'test']);

        $notifiable = new TestNotifiableUser;
        $notifiable->notify(new DeleteMissingModelNotification($model));

        NotificationTestModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertFalse(DeleteMissingModelNotification::$handled);
        $this->assertNull(\DB::table('failed_jobs')->first());
    }
}

class NotificationTestModel extends Model
{
    protected $table = 'delete_notification_test_models';

    public $timestamps = false;

    protected $guarded = [];
}

class TestNotifiableUser
{
    use Notifiable;
}

class DeleteMissingModelNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public static bool $handled = false;

    public $deleteWhenMissingModels = true;

    public function __construct(public NotificationTestModel $model)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toArray($notifiable): array
    {
        self::$handled = true;

        return ['model_id' => $this->model->id];
    }
}
