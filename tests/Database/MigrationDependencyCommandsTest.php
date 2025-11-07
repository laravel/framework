<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\MigrationConflictsCommand;
use Illuminate\Database\Console\Migrations\MigrationDependenciesCommand;
use Illuminate\Database\Console\Migrations\MigrationSuggestOrderCommand;
use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MigrationDependencyCommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testMigrationDependenciesCommandCanBeConstructed()
    {
        $migrator = m::mock(Migrator::class);
        $resolver = m::mock(MigrationDependencyResolver::class);

        $command = new MigrationDependenciesCommand($migrator, $resolver);

        $this->assertInstanceOf(MigrationDependenciesCommand::class, $command);
    }

    public function testMigrationConflictsCommandCanBeConstructed()
    {
        $migrator = m::mock(Migrator::class);
        $resolver = m::mock(MigrationDependencyResolver::class);

        $command = new MigrationConflictsCommand($migrator, $resolver);

        $this->assertInstanceOf(MigrationConflictsCommand::class, $command);
    }

    public function testMigrationSuggestOrderCommandCanBeConstructed()
    {
        $migrator = m::mock(Migrator::class);
        $resolver = m::mock(MigrationDependencyResolver::class);

        $command = new MigrationSuggestOrderCommand($migrator, $resolver);

        $this->assertInstanceOf(MigrationSuggestOrderCommand::class, $command);
    }

    public function testCommandsHaveCorrectNames()
    {
        $migrator = m::mock(Migrator::class);
        $resolver = m::mock(MigrationDependencyResolver::class);

        $dependenciesCommand = new MigrationDependenciesCommand($migrator, $resolver);
        $conflictsCommand = new MigrationConflictsCommand($migrator, $resolver);
        $suggestOrderCommand = new MigrationSuggestOrderCommand($migrator, $resolver);

        $this->assertEquals('migrate:dependencies', $dependenciesCommand->getName());
        $this->assertEquals('migrate:conflicts', $conflictsCommand->getName());
        $this->assertEquals('migrate:suggest-order', $suggestOrderCommand->getName());
    }

    public function testCommandsHaveCorrectDescriptions()
    {
        $migrator = m::mock(Migrator::class);
        $resolver = m::mock(MigrationDependencyResolver::class);

        $dependenciesCommand = new MigrationDependenciesCommand($migrator, $resolver);
        $conflictsCommand = new MigrationConflictsCommand($migrator, $resolver);
        $suggestOrderCommand = new MigrationSuggestOrderCommand($migrator, $resolver);

        $this->assertStringContainsString('dependency', $dependenciesCommand->getDescription());
        $this->assertStringContainsString('conflicts', $conflictsCommand->getDescription());
        $this->assertStringContainsString('order', $suggestOrderCommand->getDescription());
    }

    public function testCommandsHaveCorrectOptions()
    {
        $migrator = m::mock(Migrator::class);
        $resolver = m::mock(MigrationDependencyResolver::class);

        $dependenciesCommand = new MigrationDependenciesCommand($migrator, $resolver);
        $conflictsCommand = new MigrationConflictsCommand($migrator, $resolver);
        $suggestOrderCommand = new MigrationSuggestOrderCommand($migrator, $resolver);

        // Check that JSON option exists
        $this->assertTrue($dependenciesCommand->getDefinition()->hasOption('json'));
        $this->assertTrue($conflictsCommand->getDefinition()->hasOption('json'));
        $this->assertTrue($suggestOrderCommand->getDefinition()->hasOption('json'));

        // Check that dependencies command has additional options
        $this->assertTrue($dependenciesCommand->getDefinition()->hasOption('tables'));
        $this->assertTrue($dependenciesCommand->getDefinition()->hasOption('foreign-keys'));
        $this->assertTrue($dependenciesCommand->getDefinition()->hasOption('dot'));
    }
}
