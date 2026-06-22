<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\NodePackageManager;
use Laravel\Prompts\Prompt;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'dev:list')]
class DevCommandList extends Command
{
    use Prohibitable;

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
    public function handle(NodePackageManager $packageManager)
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $this->newLine();

        $devCommands = DevCommands::commands();

        if ($this->option('filter')) {
            $devCommands = array_values(
                array_filter(
                    $devCommands,
                    fn ($command) => str_contains($command['name'], $this->option('filter')) || str_contains($command['command'], $this->option('filter')),
                ),
            );
        }

        if ($this->option('except-vendor')) {
            $devCommands = array_values(
                array_filter(
                    $devCommands,
                    fn ($command) => ! $this->isVendorCommand($command),
                ),
            );
        }

        if ($this->option('only-vendor')) {
            $devCommands = array_values(
                array_filter(
                    $devCommands,
                    fn ($command) => $this->isVendorCommand($command),
                ),
            );
        }

        if ($this->option('json') || ! $this->input->isInteractive()) {
            $this->output->writeln(json_encode($devCommands));

            return self::SUCCESS;
        }

        if (empty($devCommands)) {
            $this->components->error("Your application doesn't have any dev processes matching the given criteria.");

            return self::FAILURE;
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
                $availableSourceWidth = max(0, $columns - mb_strwidth($label) - mb_strwidth($command) - mb_strwidth($dots) - $spaceBuffer);
                $source = str($source)->limit($availableSourceWidth - 1, '…')->toString();
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

    protected function isVendorCommand(array $command): bool
    {
        if (! str_contains($command['source'], '@')) {
            return str_contains($command['source'], base_path('vendor'));
        }

        [$class, $method] = explode('@', $command['source']);

        $reflection = new ReflectionClass($class);

        return str_contains($reflection->getFileName(), base_path('vendor'));
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
            ['filter', null, InputOption::VALUE_OPTIONAL, 'Filter the dev processes by name or command'],
            ['except-vendor', null, InputOption::VALUE_NONE, 'Do not display dev processes registered by vendor packages'],
            ['only-vendor', null, InputOption::VALUE_NONE, 'Only display dev processes registered by vendor packages'],
        ];
    }
}
