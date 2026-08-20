<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\DevCommand;
use Illuminate\Foundation\DevCommandMode;
use Illuminate\Foundation\DevCommands;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;

class DevCommandTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new ReflectionClass(DevCommands::class);

        foreach ([
            'mode' => DevCommandMode::TABS,
            'withTimestamps' => false,
            'autoRestart' => true,
            'bufferSize' => null,
            'streamBufferSize' => null,
        ] as $prop => $value) {
            $ref->getProperty($prop)->setValue(null, $value);
        }

        $app = new Application('/var/www/my-app');
        $app['env'] = 'testing';
        $app['config'] = new Repository(['app' => ['name' => 'Laravel']]);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandDefaultsToTabs()
    {
        $this->assertSame(
            "@laravel/multiplex --title 'artisan dev · my-app' "
                ."'server@#93c5fd,php artisan serve' 'vite@#fcd34d,npm run dev'",
            $this->command()->buildMultiplexCommandForTesting($this->devCommands())
        );
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandUsesAppNameInTitleWhenSet()
    {
        app()['config']->set('app.name', 'Acme');

        $this->assertStringContainsString(
            "--title 'artisan dev · Acme'",
            $this->command()->buildMultiplexCommandForTesting($this->devCommands())
        );
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandKeepsColonsInLabels()
    {
        $command = $this->command()->buildMultiplexCommandForTesting([
            ['name' => 'queue:work', 'command' => 'php artisan queue:work', 'source' => [], 'color' => '#c4b5fd'],
        ]);

        $this->assertStringContainsString("'queue:work@#c4b5fd,php artisan queue:work'", $command);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandModeFlags()
    {
        $this->assertStringContainsString('--stream', $this->command(['--stream' => true])->buildMultiplexCommandForTesting($this->devCommands()));
        $this->assertStringContainsString('--inline', $this->command(['--inline' => true])->buildMultiplexCommandForTesting($this->devCommands()));
        $this->assertStringNotContainsString('--tabs', $this->command(['--tabs' => true])->buildMultiplexCommandForTesting($this->devCommands()));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandUsesConfiguredModeWhenNoFlagGiven()
    {
        DevCommands::stream();

        $this->assertStringContainsString('--stream', $this->command()->buildMultiplexCommandForTesting($this->devCommands()));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandModeFlagOverridesConfiguredMode()
    {
        DevCommands::stream();

        $command = $this->command(['--tabs' => true])->buildMultiplexCommandForTesting($this->devCommands());

        $this->assertStringNotContainsString('--stream', $command);
        $this->assertStringNotContainsString('--inline', $command);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandTimestampsFromFlagOrConfiguration()
    {
        $this->assertStringContainsString('--timestamps', $this->command(['--timestamps' => true])->buildMultiplexCommandForTesting($this->devCommands()));

        DevCommands::withTimestamps();

        $this->assertStringContainsString('--timestamps', $this->command()->buildMultiplexCommandForTesting($this->devCommands()));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandNoRestartFromFlagOrConfiguration()
    {
        $this->assertStringNotContainsString('--no-restart', $this->command()->buildMultiplexCommandForTesting($this->devCommands()));
        $this->assertStringContainsString('--no-restart', $this->command(['--no-restart' => true])->buildMultiplexCommandForTesting($this->devCommands()));

        DevCommands::disableAutoRestart();

        $this->assertStringContainsString('--no-restart', $this->command()->buildMultiplexCommandForTesting($this->devCommands()));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandJsonFlag()
    {
        $this->assertStringContainsString('--json', $this->command(['--json' => true])->buildMultiplexCommandForTesting($this->devCommands()));
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testMultiplexCommandBufferSizes()
    {
        DevCommands::bufferSize(1000);
        DevCommands::streamBufferSize(2000);

        $command = $this->command()->buildMultiplexCommandForTesting($this->devCommands());

        $this->assertStringContainsString("--buffer-size='1000'", $command);
        $this->assertStringContainsString("--stream-buffer-size='2000'", $command);

        $command = $this->command([
            '--buffer-size' => '50',
            '--stream-buffer-size' => '60',
        ])->buildMultiplexCommandForTesting($this->devCommands());

        $this->assertStringContainsString("--buffer-size='50'", $command);
        $this->assertStringContainsString("--stream-buffer-size='60'", $command);
    }

    public function testConcurrentlyCommandRestartsByDefault()
    {
        $this->assertSame(
            'concurrently -c "#93c5fd,#fcd34d" "php artisan serve" "npm run dev" --names=server,vite'
                .' --restart-tries=5 --restart-after=1000',
            $this->command()->buildConcurrentlyCommandForTesting($this->devCommands())
        );
    }

    public function testConcurrentlyCommandNoRestartFromFlagOrConfiguration()
    {
        $this->assertStringContainsString(
            '--kill-others-on-fail',
            $this->command(['--no-restart' => true])->buildConcurrentlyCommandForTesting($this->devCommands())
        );

        DevCommands::disableAutoRestart();

        $this->assertStringContainsString(
            '--kill-others-on-fail',
            $this->command()->buildConcurrentlyCommandForTesting($this->devCommands())
        );
    }

    public function testConcurrentlyCommandTimestampsFromFlagOrConfiguration()
    {
        $this->assertStringNotContainsString(
            '--timestamp-format',
            $this->command()->buildConcurrentlyCommandForTesting($this->devCommands())
        );

        // concurrently only renders a timestamp when "{time}" is in the prefix, so
        // the format flag has to travel with one.
        $this->assertStringContainsString(
            '--timestamp-format="HH:mm:ss" -p "{time} [{name}]"',
            $this->command(['--timestamps' => true])->buildConcurrentlyCommandForTesting($this->devCommands())
        );

        DevCommands::withTimestamps();

        $this->assertStringContainsString(
            '--timestamp-format="HH:mm:ss" -p "{time} [{name}]"',
            $this->command()->buildConcurrentlyCommandForTesting($this->devCommands())
        );
    }

    protected function command(array $options = [])
    {
        $command = new class extends DevCommand
        {
            public function buildMultiplexCommandForTesting(array $devCommands): string
            {
                return $this->buildMultiplexCommand($devCommands);
            }

            public function buildConcurrentlyCommandForTesting(array $devCommands): string
            {
                return $this->buildConcurrentlyCommand($devCommands);
            }
        };

        $command->setInput(new ArrayInput($options, $command->getDefinition()));

        return $command;
    }

    protected function devCommands(): array
    {
        return [
            ['name' => 'server', 'command' => 'php artisan serve', 'source' => [], 'color' => '#93c5fd'],
            ['name' => 'vite', 'command' => 'npm run dev', 'source' => [], 'color' => '#fcd34d'],
        ];
    }
}
