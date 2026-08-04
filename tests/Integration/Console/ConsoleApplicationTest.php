<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Attributes\Metadata;
use Illuminate\Console\Command;
use Illuminate\Console\ContainerCommandLoader;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                FooCommandStub::class,
                ZondaCommandStub::class,
            ]);

            $artisan->add(new SymfonyCommandStub);
        });

        parent::setUp();
    }

    public function testArtisanCallUsingCommandName(): void
    {
        $this->artisan('foo:bar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameAliases(): void
    {
        $this->artisan('app:foobar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandClass(): void
    {
        $this->artisan(FooCommandStub::class, [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameUsingAsCommandAttribute(): void
    {
        $this->artisan('zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameAliasesUsingAsCommandAttribute(): void
    {
        $this->artisan('app:zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallNow(): void
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ])->run();

        $this->assertSame(0, $exitCode);
    }

    public function testArtisanWithMockCallAfterCallNow(): void
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ])->run();

        $mock = $this->artisan('foo:bar', [
            'id' => 1,
        ]);

        $this->assertSame(0, $exitCode);
        $mock->assertExitCode(0);
    }

    public function testArtisanInstantiateScheduleWhenNeed(): void
    {
        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->app[Kernel::class]->registerCommand(new ScheduleCommandStub);

        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->artisan('foo:schedule');

        $this->assertTrue($this->app->resolved(Schedule::class));
    }

    public function testArtisanQueue(): void
    {
        Queue::fake();

        $this->app[Kernel::class]->queue('foo:bar', [
            'id' => 1,
        ]);

        Queue::assertPushed(QueuedCommand::class, function ($job) {
            return $job->displayName() === 'foo:bar';
        });
    }

    public function testArtisanAllResolvesCommandMetadataThroughTheLazyLoader(): void
    {
        $commands = $this->app[Kernel::class]->all();

        $this->assertSame('artisan-command-metadata-owner', $commands['zonda']->getMetadata('owner'));
        $this->assertSame($commands['zonda'], $commands['app:zonda']);

        $kernel = $this->app[Kernel::class];
        $artisan = (new ReflectionProperty($kernel, 'artisan'))->getValue($kernel);
        $commandMap = (new ReflectionProperty($artisan, 'commandMap'))->getValue($artisan);
        $commandLoader = (new ReflectionProperty(\Symfony\Component\Console\Application::class, 'commandLoader'))->getValue($artisan);

        $this->assertSame(ZondaCommandStub::class, $commandMap['zonda']);
        $this->assertNotContains('artisan-command-metadata-owner', $commandMap, true);
        $this->assertInstanceOf(ContainerCommandLoader::class, $commandLoader);

        $loaderMap = (new ReflectionProperty($commandLoader, 'commandMap'))->getValue($commandLoader);

        $this->assertSame(ZondaCommandStub::class, $loaderMap['zonda']);
        $this->assertNotContains('artisan-command-metadata-owner', $loaderMap, true);
    }

    public function testMixedCommandCatalogsCanBeFilteredToLaravelCommands(): void
    {
        $commands = $this->app[Kernel::class]->all();
        $laravelCommands = array_filter(
            $commands,
            fn (SymfonyCommand $command) => $command instanceof Command,
        );

        $this->assertArrayHasKey('zonda', $laravelCommands);
        $this->assertArrayHasKey('symfony:metadata-control', $commands);
        $this->assertArrayNotHasKey('symfony:metadata-control', $laravelCommands);
        $this->assertFalse(method_exists($commands['symfony:metadata-control'], 'getMetadata'));
    }

    public function testDirectSymfonyCommandsRemainListableAndExecutable(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('symfony:metadata-control')
            ->doesntExpectOutputToContain('artisan-command-metadata-owner');

        $this->artisan('symfony:metadata-control')
            ->expectsOutput('Symfony command ran')
            ->assertExitCode(0);
    }

    public function testCommandMetadataIsNotIncludedInHelpOutput(): void
    {
        $this->artisan('help', ['command_name' => 'zonda'])
            ->expectsOutputToContain('Zonda command description')
            ->doesntExpectOutputToContain('artisan-command-metadata-owner');
    }

    public function testCommandMetadataIsNotCopiedToQueuedCommandPayloads(): void
    {
        Queue::fake();

        $this->app[Kernel::class]->queue('zonda', ['id' => 1]);

        Queue::assertPushed(QueuedCommand::class, function ($job) {
            return $job->displayName() === 'zonda'
                && ! str_contains(serialize($job), 'artisan-command-metadata-owner');
        });
    }

    public function testCommandMetadataIsNotCopiedToScheduledEvents(): void
    {
        $event = $this->app->make(Schedule::class)->command(ZondaCommandStub::class, ['id' => 1]);

        $this->assertSame([], $event->attributes);
        $this->assertSame('Zonda command description', $event->description);
        $this->assertStringNotContainsString('artisan-command-metadata-owner', $event->command);
    }
}

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

    protected $aliases = ['app:foobar'];

    public function handle()
    {
        //
    }
}

#[AsCommand(name: 'zonda', aliases: ['app:zonda'])]
#[Metadata(['owner' => 'artisan-command-metadata-owner'])]
class ZondaCommandStub extends Command
{
    protected $signature = 'zonda {id}';

    protected $aliases = ['app:zonda'];

    protected $description = 'Zonda command description';

    public function handle()
    {
        //
    }
}

#[AsCommand(name: 'symfony:metadata-control', description: 'Direct Symfony command')]
class SymfonyCommandStub extends SymfonyCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Symfony command ran');

        return self::SUCCESS;
    }
}

class ScheduleCommandStub extends Command
{
    protected $signature = 'foo:schedule';

    public function handle(Schedule $schedule)
    {
        //
    }
}
