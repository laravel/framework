<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PruneCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container);

        $container->singleton(DispatcherContract::class, function () {
            return new Dispatcher();
        });

        $container->alias(DispatcherContract::class, 'events');
    }

    public function testPrunableModelWithPrunableRecords()
    {
        $output = $this->artisan(['--model' => PrunableTestModelWithPrunableRecords::class]);

        $this->assertEquals(<<<'EOF'
10 [Illuminate\Tests\Database\PrunableTestModelWithPrunableRecords] records have been pruned.
20 [Illuminate\Tests\Database\PrunableTestModelWithPrunableRecords] records have been pruned.

EOF, str_replace("\r", '', $output->fetch()));
    }

    public function testPrunableTestModelWithoutPrunableRecords()
    {
        $output = $this->artisan(['--model' => PrunableTestModelWithoutPrunableRecords::class]);

        $this->assertEquals(<<<'EOF'
No prunable [Illuminate\Tests\Database\PrunableTestModelWithoutPrunableRecords] records found.

EOF, str_replace("\r", '', $output->fetch()));
    }

    public function testPrunableSoftDeletedModelWithPrunableRecords()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
        DB::connection('default')->getSchemaBuilder()->create('prunables', function ($table) {
            $table->string('value')->nullable();
            $table->datetime('deleted_at')->nullable();
        });
        DB::connection('default')->table('prunables')->insert([
            ['value' => 1, 'deleted_at' => null],
            ['value' => 2, 'deleted_at' => '2021-12-01 00:00:00'],
            ['value' => 3, 'deleted_at' => null],
            ['value' => 4, 'deleted_at' => '2021-12-02 00:00:00'],
        ]);

        $output = $this->artisan(['--model' => PrunableTestSoftDeletedModelWithPrunableRecords::class]);

        $this->assertEquals(<<<'EOF'
2 [Illuminate\Tests\Database\PrunableTestSoftDeletedModelWithPrunableRecords] records have been pruned.

EOF, str_replace("\r", '', $output->fetch()));

        $this->assertEquals(2, PrunableTestSoftDeletedModelWithPrunableRecords::withTrashed()->count());
    }

    public function testNonPrunableTest()
    {
        $output = $this->artisan(['--model' => NonPrunableTestModel::class]);

        $this->assertEquals(<<<'EOF'
No prunable [Illuminate\Tests\Database\NonPrunableTestModel] records found.

EOF, str_replace("\r", '', $output->fetch()));
    }

    public function testNonPrunableTestWithATrait()
    {
        $output = $this->artisan(['--model' => NonPrunableTrait::class]);

        $this->assertEquals(<<<'EOF'
No prunable models found.

EOF, str_replace("\r", '', $output->fetch()));
    }

    public function testTheCommandMayBePretended()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
        DB::connection('default')->getSchemaBuilder()->create('prunables', function ($table) {
            $table->string('name')->nullable();
            $table->string('value')->nullable();
        });
        DB::connection('default')->table('prunables')->insert([
            ['name' => 'zain', 'value' => 1],
            ['name' => 'patrice', 'value' => 2],
            ['name' => 'amelia', 'value' => 3],
            ['name' => 'stuart', 'value' => 4],
            ['name' => 'bello', 'value' => 5],
        ]);

        $output = $this->artisan([
            '--model' => PrunableTestModelWithPrunableRecords::class,
            '--pretend' => true,
        ]);

        $this->assertEquals(<<<'EOF'
3 [Illuminate\Tests\Database\PrunableTestModelWithPrunableRecords] records will be pruned.

EOF, str_replace("\r", '', $output->fetch()));

        $this->assertEquals(5, PrunableTestModelWithPrunableRecords::count());
    }

    public function testTheCommandMayBePretendedOnSoftDeletedModel()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
        DB::connection('default')->getSchemaBuilder()->create('prunables', function ($table) {
            $table->string('value')->nullable();
            $table->datetime('deleted_at')->nullable();
        });
        DB::connection('default')->table('prunables')->insert([
            ['value' => 1, 'deleted_at' => null],
            ['value' => 2, 'deleted_at' => '2021-12-01 00:00:00'],
            ['value' => 3, 'deleted_at' => null],
            ['value' => 4, 'deleted_at' => '2021-12-02 00:00:00'],
        ]);

        $output = $this->artisan([
            '--model' => PrunableTestSoftDeletedModelWithPrunableRecords::class,
            '--pretend' => true,
        ]);

        $this->assertEquals(<<<'EOF'
2 [Illuminate\Tests\Database\PrunableTestSoftDeletedModelWithPrunableRecords] records will be pruned.

EOF, str_replace("\r", '', $output->fetch()));

        $this->assertEquals(4, PrunableTestSoftDeletedModelWithPrunableRecords::withTrashed()->count());
    }

    protected function artisan($arguments)
    {
        $input = new ArrayInput($arguments);
        $output = new BufferedOutput;

        tap(new PruneCommand())
            ->setLaravel(Container::getInstance())
            ->run($input, $output);

        return $output;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);
    }
}

class PrunableTestModelWithPrunableRecords extends Model
{
    use MassPrunable;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function pruneAll()
    {
        event(new ModelsPruned(static::class, 10));
        event(new ModelsPruned(static::class, 20));

        return 20;
    }

    public function prunable()
    {
        return static::where('value', '>=', 3);
    }
}

class PrunableTestSoftDeletedModelWithPrunableRecords extends Model
{
    use MassPrunable, SoftDeletes;

    protected $table = 'prunables';
    protected $connection = 'default';

    public function prunable()
    {
        return static::where('value', '>=', 3);
    }
}

class PrunableTestModelWithoutPrunableRecords extends Model
{
    use Prunable;

    public function pruneAll()
    {
        return 0;
    }
}

class NonPrunableTestModel extends Model
{
    // ..
}

trait NonPrunableTrait
{
    use Prunable;
}
