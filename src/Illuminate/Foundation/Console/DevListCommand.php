<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\DevCommand;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Stringable;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'dev:list')]
class DevListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dev:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the registered dev processes';

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        $devCommands = array_map(fn ($command) => array_merge($command, [
            'source' => $this->formatSource($command['source']),
        ]), $this->filterCommands(DevCommands::commands()));

        if ($this->option('json') || ! $this->input->isInteractive()) {
            $this->output->writeln(json_encode($devCommands));

            return empty($devCommands) && $this->isFiltering() ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();

        if (empty($devCommands)) {
            if ($this->isFiltering()) {
                $this->components->error("Your application doesn't have any dev processes matching the given criteria.");

                return self::FAILURE;
            }

            $this->components->warn("Your application doesn't have any dev processes.");

            return self::SUCCESS;
        }

        $names = array_column($devCommands, 'name');
        $longestName = max(array_map(strlen(...), $names));

        $columns = Prompt::terminal()->cols();

        foreach ($devCommands as $devCommand) {
            $label = str_pad($devCommand['name'], $longestName);
            $command = $devCommand['command'];
            $source = ' '.$devCommand['source'];
            $textWidth = mb_strwidth($label) + mb_strwidth($command) + mb_strwidth($source);
            $spaceBuffer = 6;

            $dots = str_repeat('.', max(0, $columns - $textWidth - $spaceBuffer));

            // Truncate source if it doesn't fit, but ensure command is always fully visible.
            if ($textWidth + $spaceBuffer > $columns) {
                $availableSourceWidth = max(
                    0,
                    $columns - mb_strwidth($label) - mb_strwidth($command) - mb_strwidth($dots) - $spaceBuffer
                );

                $source = (new Stringable($source))->limit($availableSourceWidth - 1, '…')->value();
            }

            $this->line(
                sprintf(
                    '  <fg=%s>%s</> %s <fg=#6C7280>%s%s</>',
                    $devCommand['color'],
                    $label,
                    $command,
                    $dots,
                    $source,
                ),
            );
        }

        $countText = 'Showing ['.count($devCommands).'] dev '.(count($devCommands) === 1 ? 'command' : 'commands').' ';

        $countSpaces = max(0, $columns - mb_strwidth($countText) - 1);
        $spaces = str_repeat(' ', $countSpaces);

        $this->newLine();
        $this->line($spaces.'<fg=blue;options=bold>'.$countText.'</>');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Determine if the given command is registered by a vendor package.
     *
     * @param  array  $command
     * @return bool
     */
    protected function isVendorCommand(array $command): bool
    {
        return $command['priority'] === DevCommand::PRIORITY_VENDOR;
    }

    /**
     * Determine if any filtering options are active.
     *
     * @return bool
     */
    protected function isFiltering(): bool
    {
        return $this->option('filter') || $this->option('except-vendor') || $this->option('only-vendor');
    }

    /**
     * Filter the given commands based on the provided options.
     *
     * @param  array  $commands
     * @return array
     */
    protected function filterCommands(array $commands): array
    {
        if ($this->option('filter')) {
            $commands = array_filter(
                $commands,
                fn ($command) => str_contains($command['name'], $this->option('filter')) || str_contains($command['command'], $this->option('filter')),
            );
        }

        if ($this->option('except-vendor')) {
            $commands = array_filter(
                $commands,
                fn ($command) => ! $this->isVendorCommand($command),
            );
        }

        if ($this->option('only-vendor')) {
            $commands = array_filter(
                $commands,
                $this->isVendorCommand(...),
            );
        }

        return array_values($commands);
    }

    /**
     * Format the source information for display.
     *
     * @param  array{'file': string, 'line': int, 'class'?: string, 'function'?: string}  $source
     * @return string
     */
    protected function formatSource($source): string
    {
        $file = $source['file'] ?? null;
        $line = $source['line'] ?? null;
        $class = $source['class'] ?? null;
        $function = $source['function'] ?? null;

        if ($class) {
            return "{$class}@{$function}";
        }

        return implode(':', array_filter([$file, $line]));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['json', null, InputOption::VALUE_NONE, 'Output the dev process list as JSON'],
            ['filter', null, InputOption::VALUE_REQUIRED, 'Filter the dev processes by name or command'],
            ['except-vendor', null, InputOption::VALUE_NONE, 'Do not display dev processes registered by vendor packages'],
            ['only-vendor', null, InputOption::VALUE_NONE, 'Only display dev processes registered by vendor packages'],
        ];
    }
}
