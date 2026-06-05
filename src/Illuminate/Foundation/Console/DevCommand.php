<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\NodePackageManager;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\info;

#[AsCommand(name: 'dev')]
class DevCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dev';

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
    public function handle(NodePackageManager $packageManager)
    {
        // TODO: These probably belong elsewhere? Earlier in the process?
        DevCommands::artisan('serve --host=localhost', 'server');
        DevCommands::artisan('queue:listen --tries=1 --timeout=0', 'queue');
        DevCommands::artisan('pail --timeout=0', 'logs');
        DevCommands::node('dev', 'vite');

        $devCommands = DevCommands::getCommands();

        $commands = array_column($devCommands, 'command');
        $colors = array_column($devCommands, 'color');
        $names = array_column($devCommands, 'name');

        $longestName = max(array_map('strlen', $names));

        $this->line('');

        foreach ($devCommands as $devCommand) {
            $this->line(
                sprintf(
                    '<fg=%s>[%s]</>%s<fg=#888888>%s</>',
                    $devCommand['color'],
                    $devCommand['name'],
                    str_repeat(' ', ($longestName - strlen($devCommand['name'])) + 1),
                    $devCommand['command'],
                ),
            );
        }

        $this->line('');

        $command = $packageManager->getExecCommand(sprintf(
            'concurrently -c "%s" "%s" --names=%s --kill-others',
            implode(',', $colors),
            implode('" "', $commands),
            implode(',', $names)
        ));

        if (extension_loaded('pcntl')) {
            pcntl_exec('/usr/bin/env', ['sh', '-c', $command]);
        } else {
            passthru($command, $exitCode);
        }

        return self::SUCCESS;
    }
}
