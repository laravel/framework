<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class PrunableTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container);

        $container->singleton(DispatcherContract::class, function () {
            return new Dispatcher();
        });
        $container->alias(DispatcherContract::class, 'events');
        $this->setUpDatabase();
    }

    /**
     * @dataProvider modelProvider
     */
    public function testPrunableTraitDeletesOnBoolFlag(string $model, $expected)
    {
        (new $model())->pruneAll();

        $this->assertEquals(
            $expected,
            (new $model())->withTrashed()->count()
        );
    }

    public function modelProvider()
    {
        return [
            [
                PrunableTestSoftDeletes::class,
                4,
            ],
            [
                PrunableTestForceDeletes::class,
                0,
            ],
        ];
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
            ['id' =>  3, 'created_at' => '2021-12-01 00:00:00', 'deleted_at' => null],
            ['id' =>  4, 'created_at' => '2021-12-01 00:00:00', 'deleted_at' => '2021-12-02 00:00:00'],
        ]);
    }
}

class PrunableTestForceDeletes extends Model
{
    use Prunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function prunable()
    {
        return static::where('created_at', '<', '2021-12-02 00:00:00');
    }
}

class PrunableTestSoftDeletes extends Model
{
    use Prunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function prunable()
    {
        return static::where('created_at', '<', '2021-12-02 00:00:00');
    }

    protected function softPrune(): bool
    {
        return true;
    }
}
