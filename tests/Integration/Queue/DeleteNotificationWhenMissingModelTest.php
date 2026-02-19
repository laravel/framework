<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification as NotificationFacade;
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

    #[\Override]
    protected function tearDown(): void
    {
        DeleteWhenMissingNotification::$sent = false;

        parent::tearDown();
    }

    public function test_deleteModelWhenMissing_on_queued_notification(): void
    {
        $model = DeleteNotificationTestModel::query()->create(['name' => 'test']);

        NotificationFacade::send($model, new DeleteWhenMissingNotification($model));

        DeleteNotificationTestModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertFalse(DeleteWhenMissingNotification::$sent);
        $this->assertNull(\DB::table('failed_jobs')->first());
    }
}

class DeleteNotificationTestModel extends Model
{
    use Notifiable;

    protected $table = 'delete_notification_test_models';

    public $timestamps = false;

    protected $guarded = [];
}

#[DeleteWhenMissingModels]
class DeleteWhenMissingNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public static bool $sent = false;

    public function __construct(public DeleteNotificationTestModel $model)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        static::$sent = true;

        return new \Illuminate\Notifications\Messages\MailMessage;
    }
}
