<?php

namespace Illuminate\Tests\Integration\Migration;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Stringable;
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

    protected function tearDown(): void
    {
        Migrator::withoutMigrations([]);

        parent::tearDown();
    }

    public function testMigrate()
    {
        $this->expectInfo('Running migrations.');

        $this->expectTask('2014_10_12_000000_create_people_table', 'DONE');
        $this->expectTask('2015_10_04_000000_modify_people_table', 'DONE');
        $this->expectTask('2016_10_04_000000_modify_people_table', 'DONE');
        $this->expectTask('2017_10_04_000000_add_age_to_people', 'SKIPPED');

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/fixtures']);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'first_name'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'last_name'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('people', 'age'));
    }

    public function testMigrateWithoutOutput()
    {
        $this->app->forgetInstance('migrator');
        $this->subject = $this->app->make('migrator');

        $this->subject->run([__DIR__.'/fixtures']);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'first_name'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'last_name'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('people', 'age'));
    }

    public function testWithSkippedMigrations()
    {
        $this->app->forgetInstance('migrator');
        $this->subject = $this->app->make('migrator');

        Migrator::withoutMigrations(['2015_10_04_000000_modify_people_table.php', '2016_10_04_000000_modify_people_table']);

        $this->subject->run([__DIR__.'/fixtures']);
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('people', 'first_name'));
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('people', 'last_name'));

        Migrator::withoutMigrations([]);
        $this->subject->run([__DIR__.'/fixtures']);
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

        $this->output->shouldReceive('writeln')->times(3);

        $this->subject->run([__DIR__.'/fixtures'], ['pretend' => true]);

        $this->assertFalse(DB::getSchemaBuilder()->hasTable('people'));
    }

    public function testIgnorePretendModeForCallbackData()
    {
        // Create two tables with different columns so that we can query it later
        // with the new method DB::withoutPretending().

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

        DB::pretend(function () {
            // Returns an empty array because we are in pretend mode.
            $tablesEmpty = DB::select("SELECT name FROM sqlite_master WHERE type='table'");

            $this->assertTrue([] === $tablesEmpty);

            // Returns an array with two tables because we ignore pretend mode.
            $tablesList = DB::withoutPretending(function (): array {
                return DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            });

            $this->assertTrue([] !== $tablesList);

            // The following would not be possible in pretend mode, if the
            // method DB::withoutPretending() would not exists,
            // because nothing is executed in pretend mode.
            foreach ($tablesList as $table) {
                if (in_array($table->name, ['sqlite_sequence', 'migrations'])) {
                    continue;
                }

                $columnsEmpty = DB::select("PRAGMA table_info($table->name)");

                $this->assertTrue([] === $columnsEmpty);

                $columnsList = DB::withoutPretending(function () use ($table): array {
                    return DB::select("PRAGMA table_info($table->name)");
                });

                $this->assertTrue([] !== $columnsList);
                $this->assertCount(2, $columnsList);

                // Confirm that we are still in pretend mode. This column should
                // not be added. We query the table columns again to ensure the
                // count is still two.
                DB::statement("ALTER TABLE $table->name ADD COLUMN column_3 varchar(255) DEFAULT 'default_value' NOT NULL");

                $columnsList = DB::withoutPretending(function () use ($table): array {
                    return DB::select("PRAGMA table_info($table->name)");
                });

                $this->assertCount(2, $columnsList);
            }
        });

        Schema::dropIfExists('table_1');
        Schema::dropIfExists('table_2');
    }

    public function testIgnorePretendModeForCallbackOutputDynamicContentIsShown()
    {
        // Persist data to table we can work with.
        $this->expectInfo('Running migrations.');
        $this->expectTask('2014_10_12_000000_create_people_is_dynamic_table', 'DONE');

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/pretending/2014_10_12_000000_create_people_is_dynamic_table.php'], ['pretend' => false]);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));

        // Test the actual functionality.
        $this->expectInfo('Running migrations.');
        $this->expectTwoColumnDetail('DynamicContentIsShown');
        $this->expectBulletList([
            'create table "blogs" ("id" integer primary key autoincrement not null, "url" varchar, "name" varchar)',
            'insert into "blogs" ("url") values (\'www.janedoe.com\'), (\'www.johndoe.com\')',
            'ALTER TABLE \'pseudo_table_name\' MODIFY \'column_name\' VARCHAR(191)',
            'select * from "people"',
            'insert into "blogs" ("id", "name") values (1, \'Jane Doe Blog\')',
            'insert into "blogs" ("id", "name") values (2, \'John Doe Blog\')',
        ]);

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/pretending/2023_10_17_000000_dynamic_content_is_shown.php'], ['pretend' => true]);

        $this->assertFalse(DB::getSchemaBuilder()->hasTable('blogs'));

        Schema::dropIfExists('people');
    }

    public function testIgnorePretendModeForCallbackOutputDynamicContentNotShown()
    {
        // Persist data to table we can work with.
        $this->expectInfo('Running migrations.');
        $this->expectTask('2014_10_12_000000_create_people_non_dynamic_table', 'DONE');

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/pretending/2014_10_12_000000_create_people_non_dynamic_table.php'], ['pretend' => false]);

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('people'));

        // Test the actual functionality.
        $this->expectInfo('Running migrations.');
        $this->expectTwoColumnDetail('DynamicContentNotShown');
        $this->expectBulletList([
            'create table "blogs" ("id" integer primary key autoincrement not null, "url" varchar, "name" varchar)',
            'insert into "blogs" ("url") values (\'www.janedoe.com\'), (\'www.johndoe.com\')',
            'ALTER TABLE \'pseudo_table_name\' MODIFY \'column_name\' VARCHAR(191)',
            'select * from "people"',
        ]);

        $this->output->shouldReceive('writeln')->once();

        $this->subject->run([__DIR__.'/pretending/2023_10_17_000000_dynamic_content_not_shown.php'], ['pretend' => true]);

        $this->assertFalse(DB::getSchemaBuilder()->hasTable('blogs'));

        Schema::dropIfExists('people');
    }

    protected function expectInfo($message): void
    {
        $this->output->shouldReceive('writeln')->once()->with(m::on(
            fn ($argument) => (new Stringable($argument))->contains($message),
        ), m::any());
    }

    protected function expectTwoColumnDetail($first, $second = null)
    {
        $this->output->shouldReceive('writeln')->with(m::on(function ($argument) use ($first, $second) {
            $result = (new Stringable($argument))->contains($first);

            if ($result && $second) {
                $result = (new Stringable($argument))->contains($second);
            }

            return $result;
        }), m::any());
    }

    protected function expectBulletList($elements): void
    {
        $this->output->shouldReceive('writeln')->once()->with(m::on(function ($argument) use ($elements) {
            foreach ($elements as $element) {
                if (! (new Stringable($argument))->contains("⇂ $element")) {
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
            fn ($argument) => (new Stringable($argument))->contains(['<fg=gray></>', '<fg=gray>.</>']),
        ), m::any(), m::any());

        // Ignore duration...
        $this->output->shouldReceive('write')->with(m::on(
            fn ($argument) => (new Stringable($argument))->contains(['ms</>']),
        ), m::any(), m::any());

        $this->output->shouldReceive('write')->once()->with(m::on(
            fn ($argument) => (new Stringable($argument))->contains($description),
        ), m::any(), m::any());

        $this->output->shouldReceive('writeln')->once()->with(m::on(
            fn ($argument) => (new Stringable($argument))->contains($result),
        ), m::any());
    }
}
