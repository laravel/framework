<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class DeleteMailableWhenMissingModelTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('queue.default', 'database');
        $app['config']->set('mail.default', 'array');
        $this->driver = 'database';
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('delete_mailable_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::dropIfExists('delete_mailable_test_models');
    }

    public function test_deleteModelWhenMissing_on_queued_mailable(): void
    {
        $model = DeleteMailableTestModel::query()->create(['name' => 'test']);

        Mail::to('taylor@laravel.com')->queue(new DeleteWhenMissingMailable($model));

        DeleteMailableTestModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertNull(DB::table('failed_jobs')->first());
        $this->assertNull(DB::table('jobs')->first());
        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
    }
}

class DeleteMailableTestModel extends Model
{
    protected $table = 'delete_mailable_test_models';

    public $timestamps = false;

    protected $guarded = [];
}

#[DeleteWhenMissingModels]
class DeleteWhenMissingMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DeleteMailableTestModel $model)
    {
    }

    public function build()
    {
        return $this->subject('Test')->html('test');
    }
}
