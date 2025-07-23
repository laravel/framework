<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\Events\ModelPruningFinished;
use Illuminate\Database\Events\ModelPruningStarting;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PruneCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Application::setInstance($container = new Application(__DIR__.'/Pruning'));

        Closure::bind(
            fn () => $this->namespace = 'Illuminate\\Tests\\Database\\Pruning\\',
            $container,
            Application::class,
        )();

        $container->useAppPath(__DIR__.'/Pruning');

        $container->singleton(DispatcherContract::class, function () {
            return new Dispatcher();
        });

        $container->alias(DispatcherContract::class, 'events');
    }

    public function testPrunableModelAndExceptWithEachOther(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --models and --except options cannot be combined.');

        $this->artisan([
            '--model' => Pruning\Models\PrunableTestModelWithPrunableRecords::class,
            '--except' => Pruning\Models\PrunableTestModelWithPrunableRecords::class,
        ]);
    }

    public function testPrunableModelWithPrunableRecords()
    {
        $output = $this->artisan(['--model' => Pruning\Models\PrunableTestModelWithPrunableRecords::class]);

        $output = $output->fetch();

        $this->assertStringContainsString(
            'Illuminate\Tests\Database\Pruning\Models\PrunableTestModelWithPrunableRecords',
            $output,
        );

        $this->assertStringContainsString(
            '10 records',
            $output,
        );

        $this->assertStringContainsString(
            'Illuminate\Tests\Database\Pruning\Models\PrunableTestModelWithPrunableRecords',
            $output,
        );

        $this->assertStringContainsString(
            '20 records',
            $output,
        );
    }

    public function testPrunableTestModelWithoutPrunableRecords()
    {
        $output = $this->artisan(['--model' => Pruning\Models\PrunableTestModelWithoutPrunableRecords::class]);

        $this->assertStringContainsString(
            'No prunable [Illuminate\Tests\Database\Pruning\Models\PrunableTestModelWithoutPrunableRecords] records found.',
            $output->fetch()
        );
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

        $output = $this->artisan(['--model' => Pruning\Models\PrunableTestSoftDeletedModelWithPrunableRecords::class]);

        $output = $output->fetch();

        $this->assertStringContainsString(
            'Illuminate\Tests\Database\Pruning\Models\PrunableTestSoftDeletedModelWithPrunableRecords',
            $output,
        );

        $this->assertStringContainsString(
            '2 records',
            $output,
        );

        $this->assertEquals(2, Pruning\Models\PrunableTestSoftDeletedModelWithPrunableRecords::withTrashed()->count());
    }

    public function testNonPrunableTest()
    {
        $output = $this->artisan(['--model' => Pruning\Models\NonPrunableTestModel::class]);

        $this->assertStringContainsString(
            'No prunable [Illuminate\Tests\Database\Pruning\Models\NonPrunableTestModel] records found.',
            $output->fetch(),
        );
    }

    public function testNonPrunableTestWithATrait()
    {
        $output = $this->artisan(['--model' => Pruning\Models\NonPrunableTrait::class]);

        $this->assertStringContainsString(
            'No prunable models found.',
            $output->fetch(),
        );
    }

    public function testNonModelFilesAreIgnoredTest()
    {
        $output = $this->artisan(['--path' => 'Models']);

        $output = $output->fetch();

        $this->assertStringNotContainsString(
            'No prunable [Illuminate\Tests\Database\Pruning\Models\AbstractPrunableModel] records found.',
            $output,
        );

        $this->assertStringNotContainsString(
            'No prunable [Illuminate\Tests\Database\Pruning\Models\SomeClass] records found.',
            $output,
        );

        $this->assertStringNotContainsString(
            'No prunable [Illuminate\Tests\Database\Pruning\Models\SomeEnum] records found.',
            $output,
        );
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
            '--model' => Pruning\Models\PrunableTestModelWithPrunableRecords::class,
            '--pretend' => true,
        ]);

        $this->assertStringContainsString(
            '3 [Illuminate\Tests\Database\Pruning\Models\PrunableTestModelWithPrunableRecords] records will be pruned.',
            $output->fetch(),
        );

        $this->assertEquals(5, Pruning\Models\PrunableTestModelWithPrunableRecords::count());
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
            '--model' => Pruning\Models\PrunableTestSoftDeletedModelWithPrunableRecords::class,
            '--pretend' => true,
        ]);

        $this->assertStringContainsString(
            '2 [Illuminate\Tests\Database\Pruning\Models\PrunableTestSoftDeletedModelWithPrunableRecords] records will be pruned.',
            $output->fetch(),
        );

        $this->assertEquals(4, Pruning\Models\PrunableTestSoftDeletedModelWithPrunableRecords::withTrashed()->count());
    }

    public function testTheCommandDispatchesEvents()
    {
        $dispatcher = m::mock(DispatcherContract::class);

        $dispatcher->shouldReceive('dispatch')->once()->withArgs(function ($event) {
            return get_class($event) === ModelPruningStarting::class &&
                $event->models === [Pruning\Models\PrunableTestModelWithPrunableRecords::class];
        });
        $dispatcher->shouldReceive('listen')->once()->with(ModelsPruned::class, m::type(Closure::class));
        $dispatcher->shouldReceive('dispatch')->twice()->with(m::type(ModelsPruned::class));
        $dispatcher->shouldReceive('dispatch')->once()->withArgs(function ($event) {
            return get_class($event) === ModelPruningFinished::class &&
                $event->models === [Pruning\Models\PrunableTestModelWithPrunableRecords::class];
        });
        $dispatcher->shouldReceive('forget')->once()->with(ModelsPruned::class);

        Application::getInstance()->instance(DispatcherContract::class, $dispatcher);

        $this->artisan(['--model' => Pruning\Models\PrunableTestModelWithPrunableRecords::class]);
    }

    protected function artisan($arguments)
    {
        $input = new ArrayInput($arguments);
        $output = new BufferedOutput;

        tap(new PruneCommand())
            ->setLaravel(Application::getInstance())
            ->run($input, $output);

        return $output;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Application::setInstance(null);

        m::close();
    }
}
