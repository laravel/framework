<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Tests\Integration\Generators\TestCase;
use Mockery;
use Mockery\MockInterface;

class DeleteWhenMissingModelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        Model::getConnectionResolver()->connection()->getSchemaBuilder()->create(
            'no_soft_deletable_models',
            function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            }
        );

        Model::getConnectionResolver()->connection()->getSchemaBuilder()->create(
            'soft_deletable_models',
            function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->softDeletes();
            }
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
        Model::getConnectionResolver()->connection()->getSchemaBuilder()->dropIfExists('no_soft_deletable_models');
        Model::getConnectionResolver()->connection()->getSchemaBuilder()->dropIfExists('soft_deletable_models');
    }

    private function setUpServiceToNeverBeCalled(HandlingServiceJob $job): void
    {
        $this->app->instance(
            Service::class,
            Mockery::mock(
                Service::class,
                function (MockInterface $mock) use ($job): void {
                    $mock
                        ->shouldReceive('execute')
                        ->withArgs(
                            fn (Model $model): bool => $model->getKey() === $job->model->getKey()
                        )
                        ->never();
                }
            )
        );
    }

    public function testItDoesNotDiscardQueuedJobIfModelHasBeenDeletedAndDeleteWhenMissingModelsIsFalse(): void
    {
        $noSoftDeletableModel = NoSoftDeletableModel::create();
        $job = new DoNotDeleteWhenMissingModelsHandlingServiceJob($noSoftDeletableModel);

        $this->expectExceptionObject(
            (new ModelNotFoundException)->setModel($noSoftDeletableModel::class)
        );

        $noSoftDeletableModel->delete();
        dispatch($job);
    }

    public function testItDoesNotDiscardQueuedJobIfModelHasBeenSoftDeletedAndDeleteWhenMissingModelsIsFalse(): void
    {
        $softDeletableModel = SoftDeletableModel::create();
        $job = new DoNotDeleteWhenMissingModelsHandlingServiceJob($softDeletableModel);

        $this->expectExceptionObject(
            (new ModelNotFoundException)->setModel($softDeletableModel::class)
        );

        $softDeletableModel->delete();
        dispatch($job);
    }

    public function testItDiscardsQueuedJobIfModelHasBeenDeletedAndDeleteWhenMissingModelsIsTrue(): void
    {
        $noSoftDeletableModel = NoSoftDeletableModel::create();
        $job = new DeleteWhenMissingModelsHandlingServiceJob($noSoftDeletableModel);

        $this->setUpServiceToNeverBeCalled($job);

        $noSoftDeletableModel->delete();
        dispatch($job);
    }

    public function testItDiscardsQueuedJobIfModelHasBeenSoftDeletedAndDeleteWhenMissingModelsIsTrue(): void
    {
        $softDeletableModel = SoftDeletableModel::create();
        $job = new DeleteWhenMissingModelsHandlingServiceJob($softDeletableModel);

        $this->setUpServiceToNeverBeCalled($job);

        $softDeletableModel->delete();
        dispatch($job);
    }
}

class NoSoftDeletableModel extends Model
{
}

class SoftDeletableModel extends Model
{
    use SoftDeletes;
}

interface Service
{
    public function execute(Model $exampleModel): void;
}

abstract class HandlingServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Model $model,
    )
    {
    }

    public function handle(Service $service): void
    {
        $service->execute($this->model);
    }
}

final class DoNotDeleteWhenMissingModelsHandlingServiceJob extends HandlingServiceJob
{
    public bool $deleteWhenMissingModels = false;
}

final class DeleteWhenMissingModelsHandlingServiceJob extends HandlingServiceJob
{
    public bool $deleteWhenMissingModels = true;
}
