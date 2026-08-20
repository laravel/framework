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
use Illuminate\Tests\App\Models\Prunable\Console\NonPrunableTestModel;
use Illuminate\Tests\App\Models\Prunable\Console\NonPrunableTrait;
use Illuminate\Tests\App\Models\Prunable\Console\PrunableTestModelWithoutPrunableRecords;
use Illuminate\Tests\App\Models\Prunable\Console\PrunableTestModelWithPrunableRecords;
use Illuminate\Tests\App\Models\Prunable\Console\PrunableTestSoftDeletedModelWithPrunableRecords;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PruneCommandTest extends TestCase
{
    protected function setUp(): void
    {
        Application::setInstance($container = new Application(__DIR__.'/../App'));

        Closure::bind(
            fn () => $this->namespace = 'Illuminate\\Tests\\App\\',
            $container,
            Application::class,
        )();

        $container->useAppPath(__DIR__.'/../App');

        $container->singleton(DispatcherContract::class, function () {
            return new Dispatcher();
        });

        $container->alias(DispatcherContract::class, 'events');
    }

    public function testPrunableModelAndExceptWithEachOther(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('The --model and --except options cannot be combined.'));

        $this->artisan([
            '--model' => PrunableTestModelWithPrunableRecords::class,
            '--except' => PrunableTestModelWithPrunableRecords::class,
        ]);
    }

    public function testPrunableModelWithPrunableRecords()
    {
        $output = $this->artisan(['--model' => PrunableTestModelWithPrunableRecords::class]);

        $output = $output->fetch();

        $this->assertStringContainsString(
            'Illuminate\Tests\App\Models\Prunable\Console\PrunableTestModelWithPrunableRecords',
            $output,
        );

        $this->assertStringContainsString(
            '10 records',
            $output,
        );

        $this->assertStringContainsString(
            'Illuminate\Tests\App\Models\Prunable\Console\PrunableTestModelWithPrunableRecords',
            $output,
        );

        $this->assertStringContainsString(
            '20 records',
            $output,
        );
    }

    public function testPrunableTestModelWithoutPrunableRecords()
    {
        $output = $this->artisan(['--model' => PrunableTestModelWithoutPrunableRecords::class]);

        $this->assertStringContainsString(
            'No prunable [Illuminate\Tests\App\Models\Prunable\Console\PrunableTestModelWithoutPrunableRecords] records found.',
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

        $output = $this->artisan(['--model' => PrunableTestSoftDeletedModelWithPrunableRecords::class]);

        $output = $output->fetch();

        $this->assertStringContainsString(
            'Illuminate\Tests\App\Models\Prunable\Console\PrunableTestSoftDeletedModelWithPrunableRecords',
            $output,
        );

        $this->assertStringContainsString(
            '2 records',
            $output,
        );

        $this->assertEquals(2, PrunableTestSoftDeletedModelWithPrunableRecords::withTrashed()->count());
    }

    public function testNonPrunableTest()
    {
        $output = $this->artisan(['--model' => NonPrunableTestModel::class]);

        $this->assertStringContainsString(
            'No prunable [Illuminate\Tests\App\Models\Prunable\Console\NonPrunableTestModel] records found.',
            $output->fetch(),
        );
    }

    public function testNonPrunableTestWithATrait()
    {
        $output = $this->artisan(['--model' => NonPrunableTrait::class]);

        $this->assertStringContainsString(
            'No prunable models found.',
            $output->fetch(),
        );
    }

    public function testNonModelFilesAreIgnoredTest()
    {
        $output = $this->artisan(['--path' => 'Models/Prunable/Console']);

        $output = $output->fetch();

        $this->assertStringNotContainsString(
            'No prunable [Illuminate\Tests\App\Models\Prunable\Console\AbstractPrunableModel] records found.',
            $output,
        );

        $this->assertStringNotContainsString(
            'No prunable [Illuminate\Tests\App\Models\Prunable\Console\SomeClass] records found.',
            $output,
        );

        $this->assertStringNotContainsString(
            'No prunable [Illuminate\Tests\App\Models\Prunable\Console\SomeEnum] records found.',
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
            '--model' => PrunableTestModelWithPrunableRecords::class,
            '--pretend' => true,
        ]);

        $this->assertStringContainsString(
            '3 [Illuminate\Tests\App\Models\Prunable\Console\PrunableTestModelWithPrunableRecords] records will be pruned.',
            $output->fetch(),
        );

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

        $this->assertStringContainsString(
            '2 [Illuminate\Tests\App\Models\Prunable\Console\PrunableTestSoftDeletedModelWithPrunableRecords] records will be pruned.',
            $output->fetch(),
        );

        $this->assertEquals(4, PrunableTestSoftDeletedModelWithPrunableRecords::withTrashed()->count());
    }

    public function testTheCommandDispatchesEvents()
    {
        $dispatcher = Mockery::mock(DispatcherContract::class);

        $dispatcher->expects('dispatch')->withArgs(function ($event) {
            return get_class($event) === ModelPruningStarting::class &&
                $event->models === [PrunableTestModelWithPrunableRecords::class];
        });
        $dispatcher->expects('listen')->with(ModelsPruned::class, Mockery::type(Closure::class));
        $dispatcher->expects('dispatch')->times(2)->with(Mockery::type(ModelsPruned::class));
        $dispatcher->expects('dispatch')->withArgs(function ($event) {
            return get_class($event) === ModelPruningFinished::class &&
                $event->models === [PrunableTestModelWithPrunableRecords::class];
        });
        $dispatcher->expects('forget')->with(ModelsPruned::class);

        Application::getInstance()->instance(DispatcherContract::class, $dispatcher);

        $this->artisan(['--model' => PrunableTestModelWithPrunableRecords::class]);
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
}
