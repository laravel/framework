<?php

namespace Illuminate\Tests\Integration\Migration;

use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\Mock;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class MigratorTest extends TestCase
{
    /**
     * @var Mock
     */
    private $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = Mockery::mock(OutputInterface::class);
        $this->subject = $this->app->make('migrator');
        $this->subject->setOutput($this->output);
        $this->subject->getRepository()->createRepository();
    }

    public function testMigrate()
    {
        $this->expectOutput('<comment>Migrating:</comment> 2014_10_12_000000_create_people_table');
        $this->expectOutput(Mockery::pattern('#<info>Migrated:</info>  2014_10_12_000000_create_people_table (.*)#'));
        $this->expectOutput('<comment>Migrating:</comment> 2015_10_04_000000_modify_people_table');
        $this->expectOutput(Mockery::pattern('#<info>Migrated:</info>  2015_10_04_000000_modify_people_table (.*)#'));
        $this->expectOutput('<comment>Migrating:</comment> 2016_10_04_000000_modify_people_table');
        $this->expectOutput(Mockery::pattern('#<info>Migrated:</info>  2016_10_04_000000_modify_people_table (.*)#'));

        $this->subject->run([__DIR__.'/fixtures']);

        self::assertTrue(DB::getSchemaBuilder()->hasTable('people'));
        self::assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'first_name'));
        self::assertTrue(DB::getSchemaBuilder()->hasColumn('people', 'last_name'));
    }

    public function testRollback()
    {
        $this->getConnection()->statement('CREATE TABLE people(id INT, first_name VARCHAR, last_name VARCHAR);');
        $this->subject->getRepository()->log('2014_10_12_000000_create_people_table', 1);
        $this->subject->getRepository()->log('2015_10_04_000000_modify_people_table', 1);
        $this->subject->getRepository()->log('2016_10_04_000000_modify_people_table', 1);

        $this->expectOutput('<comment>Rolling back:</comment> 2016_10_04_000000_modify_people_table');
        $this->expectOutput(Mockery::pattern('#<info>Rolled back:</info>  2016_10_04_000000_modify_people_table (.*)#'));
        $this->expectOutput('<comment>Rolling back:</comment> 2015_10_04_000000_modify_people_table');
        $this->expectOutput(Mockery::pattern('#<info>Rolled back:</info>  2015_10_04_000000_modify_people_table (.*)#'));
        $this->expectOutput('<comment>Rolling back:</comment> 2014_10_12_000000_create_people_table');
        $this->expectOutput(Mockery::pattern('#<info>Rolled back:</info>  2014_10_12_000000_create_people_table (.*)#'));

        $this->subject->rollback([__DIR__.'/fixtures']);

        self::assertFalse(DB::getSchemaBuilder()->hasTable('people'));
    }

    public function testPretendMigrate()
    {
        $this->expectOutput('<info>CreatePeopleTable:</info> create table "people" ("id" integer not null primary key autoincrement, "name" varchar not null, "email" varchar not null, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime)');
        $this->expectOutput('<info>CreatePeopleTable:</info> create unique index "people_email_unique" on "people" ("email")');
        $this->expectOutput('<info>ModifyPeopleTable:</info> alter table "people" add column "first_name" varchar');
        $this->expectOutput('<info>2016_10_04_000000_modify_people_table:</info> alter table "people" add column "last_name" varchar');

        $this->subject->run([__DIR__.'/fixtures'], ['pretend' => true]);

        self::assertFalse(DB::getSchemaBuilder()->hasTable('people'));
    }

    private function expectOutput($argument): void
    {
        $this->output->shouldReceive('writeln')->once()->with($argument);
    }
}
