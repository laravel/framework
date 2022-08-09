<?php

namespace Illuminate\Tests\Database;

use _PHPStan_9a6ded56a\Nette\Neon\Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\TestCase;

class PrunableTraitTest extends TestCase
{
    protected $dispatchSpy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatchSpy = \Mockery::spy(Dispatcher::class);

        Container::setInstance($container = new Container);
        $container->singleton(DispatcherContract::class, function() {
            return $this->dispatchSpy;
        });
        $container->alias(DispatcherContract::class, 'events');
        $this->setUpDatabase();
    }

    public function testPrunableDeletesRecordsMatchingCondition()
    {
        (new PrunableTestModel())->pruneAll();

        $this->assertEquals(
            collect(
                [
                    3,
                    4
                ]
            ),
            PrunableTestModel::withTrashed()->pluck('id')
        );
    }

    public function testPrunableEmitsEvents()
    {
        $this->dispatchSpy
            ->shouldReceive('dispatch')
            ->withArgs(function(ModelsPruned $modelsPruned) {
                $this->assertEquals(
                    [$modelsPruned->count, $modelsPruned->model],
                    [2, 'Illuminate\Tests\Database\PrunableTestModel']
                );
                return true;
            });

        (new PrunableTestModel())->pruneAll();

        $this->assertEquals(
            collect(
                [3, 4]
            ),
            PrunableTestModel::withTrashed()->pluck('id')
        );
    }

    public function testPrunableCallsPruningHook()
    {
        $this->expectExceptionMessage('thrown from pruning hook');

        $class = new class extends PrunableTestModel {
            public function pruning()
            {
                throw new Exception('thrown from pruning hook');
            }
        };

        $class->pruneAll();
    }

    protected function setUpDatabase()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
        DB::connection('default')->getSchemaBuilder()->create('prunables', function ($table) {
            $table->id();
            $table->datetime('created_at');
            $table->datetime('updated_at')->nullable();
            $table->datetime('deleted_at')->nullable();
        });

        DB::connection('default')->table('prunables')->insert([
            ['id' =>  1, 'created_at' => '2021-12-01 00:00:00', 'deleted_at' => null],
            ['id' =>  2, 'created_at' => '2021-12-01 00:00:00', 'deleted_at' => '2021-12-01 00:00:00'],
            ['id' =>  3, 'created_at' => '2021-12-03 00:00:00', 'deleted_at' => null],
            ['id' =>  4, 'created_at' => '2021-12-03 00:00:00', 'deleted_at' => '2021-12-02 00:00:00'],
        ]);
    }
}

class PrunableTestModel extends Model
{
    use Prunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function prunable()
    {
        return static::where('created_at', '<', '2021-12-02 00:00:00');
    }
}
