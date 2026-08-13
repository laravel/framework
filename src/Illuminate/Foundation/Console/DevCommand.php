<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Foundation\DevCommandMode;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\NodePackageManager;
use Symfony\Component\Console\Attribute\AsCommand;

use function Termwind\terminal;

/**
 * @phpstan-import-type DevCommandArray from \Illuminate\Foundation\DevCommands
 */
#[AsCommand(name: 'dev')]
class DevCommand extends Command
{
    use Prohibitable;

    /**
     * The version of `@laravel/multiplex` to use.
     *
     * @var string
     */
    protected const MULTIPLEX_VERSION = '0.4';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev
        {--s|stream : Start in stream mode}
        {--t|tabs : Start in tabs mode}
        {--i|inline : Print output inline instead of rendering the TUI (the default when not a TTY)}
        {--timestamps : Display timestamps on each output line}
        {--no-restart : Disable auto-restart on crash}
        {--json : Emit newline-delimited JSON events. Implies --inline}
        {--buffer-size= : Set the max lines per command buffer}
        {--stream-buffer-size= : Set the max lines in the stream buffer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the dev processes';

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle(NodePackageManager $packageManager): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $devCommands = DevCommands::commands();

        return match (PHP_OS_FAMILY) {
            'Windows' => $this->runViaConcurrently($devCommands, $packageManager),
            default => $this->runViaMultiplex($devCommands, $packageManager),
        };
    }

    /**
     * Run the dev commands via `@laravel/multiplex`.
     *
     * @param  DevCommandArray[]  $devCommands
     * @param  NodePackageManager  $packageManager
     * @return int
     */
    protected function runViaMultiplex(array $devCommands, NodePackageManager $packageManager): int
    {
        $multiplexCommand = $this->buildMultiplexCommand($devCommands);

        $command = $packageManager->getExecCommand($multiplexCommand);

        if (extension_loaded('pcntl')) {
            pcntl_exec('/usr/bin/env', ['sh', '-c', $command]);
        }

        passthru($command, $exitCode);

        return $exitCode;
    }

    /**
     * Build the command to run `@laravel/multiplex` with the given dev commands.
     *
     * @param  DevCommandArray[]  $devCommands
     * @return string
     */
    protected function buildMultiplexCommand(array $devCommands): string
    {
        $args = collect($devCommands)
            ->map(fn ($devCommand) => implode(',', [
                $devCommand['name'].'@'.$devCommand['color'],
                $devCommand['command'],
            ]));

        $mode = match (true) {
            $this->option('tabs') => DevCommandMode::TABS,
            $this->option('stream') => DevCommandMode::STREAM,
            $this->option('inline') => DevCommandMode::INLINE,
            default => DevCommands::mode(),
        };

        $flags = collect([
            'stream' => $mode === DevCommandMode::STREAM,
            'inline' => $mode === DevCommandMode::INLINE,
            'timestamps' => $this->option('timestamps') || DevCommands::shouldIncludeTimestamps(),
            'no-restart' => $this->option('no-restart') || ! DevCommands::shouldAutoRestart(),
            'json' => $this->option('json'),
        ])
            ->filter()
            ->keys()
            ->map(fn ($flag) => "--$flag");

        if ($bufferSize = $this->option('buffer-size') ?? DevCommands::getBufferSize()) {
            $flags->push('--buffer-size='.escapeshellarg($bufferSize));
        }

        if ($streamBufferSize = $this->option('stream-buffer-size') ?? DevCommands::getStreamBufferSize()) {
            $flags->push('--stream-buffer-size='.escapeshellarg($streamBufferSize));
        }

        $title = 'artisan dev · '.(config('app.name') === 'Laravel' ? basename(base_path()) : config('app.name'));

        $command = '@laravel/multiplex@'.self::MULTIPLEX_VERSION.' --title '.escapeshellarg($title);

        if (! $flags->isEmpty()) {
            $command .= ' '.$flags->implode(' ');
        }

        $command .= ' '.$args->map(escapeshellarg(...))->implode(' ');

        return $command;
    }

    /**
     * Run the dev commands via `concurrently`.
     *
     * @param  DevCommandArray[]  $devCommands
     * @param  NodePackageManager  $packageManager
     * @return int
     */
    protected function runViaConcurrently(array $devCommands, NodePackageManager $packageManager): int
    {
        $concurrentlyCommand = $this->buildConcurrentlyCommand($devCommands);
        $command = $packageManager->getExecCommand($concurrentlyCommand);

        $names = array_column($devCommands, 'name');
        $longestName = max(array_map(strlen(...), $names));
        $columns = getenv('COLUMNS');

        putenv('COLUMNS='.max(terminal()->width() - $longestName - 4, 1));

        foreach ($devCommands as $devCommand) {
            $this->line(
                sprintf(
                    '<fg=%s>[%s]</>%s%s',
                    $devCommand['color'],
                    $devCommand['name'],
                    str_repeat(' ', ($longestName - strlen($devCommand['name'])) + 1),
                    $devCommand['command'],
                ),
            );
        }

        $this->line('');

        passthru($command, $exitCode);

        $columns === false ? putenv('COLUMNS') : putenv("COLUMNS={$columns}");

        return $exitCode;
    }

    /**
     * Build the command to run `concurrently` with the given dev commands.
     *
     * @param  DevCommandArray[]  $devCommands
     * @return string
     */
    protected function buildConcurrentlyCommand(array $devCommands): string
    {
        $names = array_column($devCommands, 'name');
        $commands = array_column($devCommands, 'command');
        $colors = array_column($devCommands, 'color');

        $command = sprintf(
            'concurrently -c "%s" "%s" --names=%s',
            implode(',', $colors),
            implode('" "', $commands),
            implode(',', $names),
        );

        if (DevCommands::shouldAutoRestart() && ! $this->option('no-restart')) {
            $command .= ' --restart-tries=5 --restart-after=1000';
        } else {
            $command .= ' --kill-others-on-fail';
        }

        if ($this->option('timestamps') || DevCommands::shouldIncludeTimestamps()) {
            $command .= ' --timestamp-format="HH:mm:ss" -p "{time} [{name}]"';
        }

        return $command;
    }
}
