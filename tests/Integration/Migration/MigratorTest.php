<?php

namespace Illuminate\Tests\Integration\Migration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class MigratorTest extends TestCase
{
    /**
     * @var \Mockery\Mock
     */
    private $output;

    public $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = m::mock(OutputInterface::class);
        $this->subject = $this->app->make('migrator');
        $this->subject->setOutput($this->output);
        $this->subject->getRepository()->createRepository();
    }

    public function testMigrate()
    {
        $this->expectInfo('Running migrations.');

        $this->expectTask('2014_10_12_000000_create_people_table', 'DONE');
        $this->expectTask('2015_10_04_000000_modify_people_table', 'DONE');
        $this->expectTask('2016_10_04_000000_modify_people_table', 'DONE');

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/fixtures']);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'first_name'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'last_name'));
    }

    public function testMigrateWithoutOutput()
    {
        $this->app->forgetInstance('migrator');
        $this->subject = $this->app->make('migrator');

        $this->subject->run([__DIR__.'/fixtures']);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'first_name'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'last_name'));
    }

    public function testRollback()
    {
        $this->getConnection()->statement('CREATE TABLE people(id INT, first_name VARCHAR, last_name VARCHAR);');
        $this->subject->getRepository()->log('2014_10_12_000000_create_people_table', 1);
        $this->subject->getRepository()->log('2015_10_04_000000_modify_people_table', 1);
        $this->subject->getRepository()->log('2016_10_04_000000_modify_people_table', 1);

        $this->expectInfo('Rolling back migrations.');

        $this->expectTask('2016_10_04_000000_modify_people_table', 'DONE');
        $this->expectTask('2015_10_04_000000_modify_people_table', 'DONE');
        $this->expectTask('2014_10_12_000000_create_people_table', 'DONE');

        $this->output->shouldReceive('writeln')->once();

        $this->subject->rollback([__DIR__.'/fixtures']);

        $this->assertFalse(DB::getSchemaBuilder()->hasTable('people'));
    }

    public function testPretendMigrate()
    {
        $this->expectInfo('Running migrations.');

        $this->expectTwoColumnDetail('CreatePeopleTable');
        $this->expectBulletList([
            'create table "people" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime)',
            'create unique index "people_email_unique" on "people" ("email")',
        ]);

        $this->expectTwoColumnDetail('ModifyPeopleTable');
        $this->expectBulletList(['alter table "people" add column "first_name" varchar']);

        $this->expectTwoColumnDetail('2016_10_04_000000_modify_people_table');
        $this->expectBulletList(['alter table "people" add column "last_name" varchar']);

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/fixtures'], ['pretend' => true]);

        $this->assertFalse(DB::getSchemaBuilder()->hasTable('people'));
    }


    public function testIgnorePretendModeForCallbackReturnsValues()
    {
        // Create two tables with different columns so that we can query it later
        // with the new method DB::ignorePretendModeForCallback().

        Schema::create('table_1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('column_1');
        });

        Schema::create('table_2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('column_2')->default('default_value');
        });

        // From here on we simulate to be in pretend mode. This normally is done by
        // running the migration with the option --pretend.

        DB::pretend(function ()
        {
            $tablesEmpty = DB::select("SELECT name FROM sqlite_master WHERE type='table'");

            $this->assertTrue([] === $tablesEmpty);

            $tablesList = DB::ignorePretendModeForCallback(function (): array {
                return DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            });

            $this->assertTrue([] !== $tablesList);

            // The following would not be possible in pretend mode, if the
            // method DB::ignorePretendModeForCallback() would not exists,
            // because nothing is executed in pretend mode.

            foreach ($tablesList as $table) {

                if (in_array($table->name, ['sqlite_sequence', 'migrations'])) {
                    continue;
                }

                $columnsEmpty = DB::select("PRAGMA table_info($table->name)");

                $this->assertTrue([] === $columnsEmpty);

                $columnsList = DB::ignorePretendModeForCallback(function () use ($table): array {
                    return DB::select("PRAGMA table_info($table->name)");
                });

                $this->assertTrue([] !== $columnsList);
                $this->assertCount(2, $columnsList);

                // Prove that we are still in pretend mode this column will not be added.
                // We query the table columns again to ensure count is still two.

                DB::statement("ALTER TABLE $table->name ADD COLUMN column_3 varchar(255) DEFAULT 'default_value' NOT NULL");

                $columnsList = DB::ignorePretendModeForCallback(function () use ($table): array {
                    return DB::select("PRAGMA table_info($table->name)");
                });

                $this->assertCount(2, $columnsList);
            }
        });
    }

    protected function expectInfo($message): void
    {
        $this->output->shouldReceive('writeln')->once()->with(m::on(
            fn ($argument) => str($argument)->contains($message),
        ), m::any());
    }

    protected function expectTwoColumnDetail($first, $second = null)
    {
        $this->output->shouldReceive('writeln')->with(m::on(function ($argument) use ($first, $second) {
            $result = str($argument)->contains($first);

            if ($result && $second) {
                $result = str($argument)->contains($second);
            }

            return $result;
        }), m::any());
    }

    protected function expectBulletList($elements): void
    {
        $this->output->shouldReceive('writeln')->once()->with(m::on(function ($argument) use ($elements) {
            foreach ($elements as $element) {
                if (! str($argument)->contains("⇂ $element")) {
                    return false;
                }
            }

            return true;
        }), m::any());
    }

    protected function expectTask($description, $result): void
    {
        // Ignore dots...
        $this->output->shouldReceive('write')->with(m::on(
            fn ($argument) => str($argument)->contains(['<fg=gray></>', '<fg=gray>.</>']),
        ), m::any(), m::any());

        // Ignore duration...
        $this->output->shouldReceive('write')->with(m::on(
            fn ($argument) => str($argument)->contains(['ms</>']),
        ), m::any(), m::any());

        $this->output->shouldReceive('write')->once()->with(m::on(
            fn ($argument) => str($argument)->contains($description),
        ), m::any(), m::any());

        $this->output->shouldReceive('writeln')->once()->with(m::on(
            fn ($argument) => str($argument)->contains($result),
        ), m::any());
    }
}
