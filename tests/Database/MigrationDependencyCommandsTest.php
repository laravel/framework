<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Console\Migrations\MigrationConflictsCommand;
use Illuminate\Database\Console\Migrations\MigrationDependenciesCommand;
use Illuminate\Database\Console\Migrations\MigrationSuggestOrderCommand;
use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationDependencyCommandsTest extends TestCase
{
    protected $migrator;
    protected $resolver;
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrator = m::mock(Migrator::class);
        $this->resolver = m::mock(MigrationDependencyResolver::class);
        $this->container = m::mock(Container::class);

        $this->container->shouldReceive('environment')->andReturn('testing');
        $this->container->shouldReceive('runningUnitTests')->andReturn(true);
        $this->container->shouldReceive('make')->with('migrator')->andReturn($this->migrator);
        $this->container->shouldReceive('make')->with('migration.dependency.resolver')->andReturn($this->resolver);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testMigrationDependenciesCommandExecutes()
    {
        $command = new MigrationDependenciesCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn([
                'dependencies' => [],
                'tables' => [],
                'foreignKeys' => [],
                'conflicts' => [],
                'suggestedOrder' => [],
            ]);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('Migration Dependency Analysis', $tester->getDisplay());
    }

    public function testMigrationConflictsCommandExecutesWithNoConflicts()
    {
        $command = new MigrationConflictsCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn([
                'conflicts' => [],
            ]);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('No conflicts detected', $tester->getDisplay());
    }

    public function testMigrationConflictsCommandDetectsConflicts()
    {
        $command = new MigrationConflictsCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn([
                'conflicts' => [
                    [
                        'type' => 'missing_table',
                        'migration' => '2024_01_01_000000_test_migration',
                        'message' => 'Missing table dependency',
                        'severity' => 'error',
                    ],
                ],
            ]);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('Migration Conflicts Detected', $tester->getDisplay());
    }

    public function testMigrationSuggestOrderCommandExecutes()
    {
        $command = new MigrationSuggestOrderCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn([
                'suggestedOrder' => ['2024_01_01_000000_create_users_table'],
                'dependencies' => ['2024_01_01_000000_create_users_table' => []],
                'conflicts' => [],
            ]);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertStringContainsString('Suggested Migration Execution Order', $tester->getDisplay());
    }

    public function testCommandsOutputJsonWhenRequested()
    {
        $analysisData = [
            'dependencies' => [],
            'tables' => [],
            'foreignKeys' => [],
            'conflicts' => [],
            'suggestedOrder' => [],
        ];

        // Test MigrationDependenciesCommand JSON output
        $command = new MigrationDependenciesCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn($analysisData);

        $tester = new CommandTester($command);
        $tester->execute(['--json' => true]);

        $this->assertJson($tester->getDisplay());
    }

    public function testMigrationConflictsCommandJsonOutput()
    {
        $command = new MigrationConflictsCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $conflicts = [
            [
                'type' => 'missing_table',
                'migration' => '2024_01_01_000000_test_migration',
                'message' => 'Missing table dependency',
                'severity' => 'error',
            ],
        ];

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn(['conflicts' => $conflicts]);

        $tester = new CommandTester($command);
        $tester->execute(['--json' => true]);

        $this->assertJson($tester->getDisplay());

        $output = json_decode($tester->getDisplay(), true);
        $this->assertTrue($output['hasConflicts']);
        $this->assertEquals(1, $output['totalConflicts']);
        $this->assertEquals($conflicts, $output['conflicts']);
    }

    public function testMigrationSuggestOrderCommandJsonOutput()
    {
        $command = new MigrationSuggestOrderCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $analysisData = [
            'suggestedOrder' => ['2024_01_01_000000_create_users_table'],
            'dependencies' => ['2024_01_01_000000_create_users_table' => []],
            'conflicts' => [],
        ];

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andReturn($analysisData);

        $tester = new CommandTester($command);
        $tester->execute(['--json' => true]);

        $this->assertJson($tester->getDisplay());

        $output = json_decode($tester->getDisplay(), true);
        $this->assertFalse($output['hasConflicts']);
        $this->assertEquals(1, $output['totalMigrations']);
        $this->assertEquals(['2024_01_01_000000_create_users_table'], $output['suggestedOrder']);
    }

    public function testCommandsHandleExceptions()
    {
        $command = new MigrationDependenciesCommand($this->migrator, $this->resolver);
        $command->setLaravel($this->container);

        $this->resolver->shouldReceive('analyzeDependencies')
            ->once()
            ->andThrow(new \Exception('Test exception'));

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringContainsString('Failed to analyze dependencies', $tester->getDisplay());
    }
}
