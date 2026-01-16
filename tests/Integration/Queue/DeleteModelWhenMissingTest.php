<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
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

    protected function defineDatabaseMigrations()
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

    #[\Override]
    protected function tearDown(): void
    {
        DeleteMissingModelJob::$handled = false;

        parent::tearDown();
    }

    public function test_deleteModelWhenMissing_and_display_name(): void
    {
        $model = MyTestModel::query()->create(['name' => 'test']);

        DeleteMissingModelJob::dispatch($model);

        MyTestModel::query()->where('name', 'test')->delete();

        $this->runQueueWorkerCommand(['--once' => '1']);

        $this->assertFalse(DeleteMissingModelJob::$handled);
        $this->assertNull(\DB::table('failed_jobs')->first());
    }
}

class DeleteMissingModelJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Dispatchable;
    use SerializesModels;

    public static bool $handled = false;

    public $deleteWhenMissingModels = true;

    public function __construct(public MyTestModel $model)
    {
    }

    public function displayName(): string
    {
        return 'sorry-ma-forgot-to-take-out-the-trash';
    }

    public function handle()
    {
        self::$handled = true;
    }
}

class MyTestModel extends Model
{
    protected $table = 'delete_model_test_models';

    public $timestamps = false;

    protected $guarded = [];
}
