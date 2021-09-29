<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Events\Dispatcher;
use Mockery as m;
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

    public function testNonPrunableTest()
    {
        $output = $this->artisan(['--model' => NonPrunableTestModel::class]);

        $this->assertEquals(<<<'EOF'
No prunable [Illuminate\Tests\Database\NonPrunableTestModel] records found.

EOF, str_replace("\r", '', $output->fetch()));
    }

    public function testTheCommandMayBePretended()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
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
        $resolver = m::mock(ConnectionResolverInterface::class, ['connection' => $db->getConnection('default')]);
        PrunableTestModelWithPrunableRecords::setConnectionResolver($resolver);

        $output = $this->artisan([
            '--model' => PrunableTestModelWithPrunableRecords::class,
            '--pretend' => true,
        ]);

        $this->assertEquals(<<<'EOF'
3 [Illuminate\Tests\Database\PrunableTestModelWithPrunableRecords] records will be pruned.

EOF, str_replace("\r", '', $output->fetch()));

        $this->assertEquals(5, PrunableTestModelWithPrunableRecords::count());
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
