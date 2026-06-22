<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\NodePackageManager;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Attribute\AsCommand;

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
        $names = array_column($devCommands, 'name');
        $longestName = max(array_map(strlen(...), $names));

        $cols = Prompt::terminal()->cols();

        foreach ($devCommands as $devCommand) {
            $label = str_pad($devCommand['name'], $longestName);
            $command = $devCommand['command'];
            $source = ' '.$devCommand['source'];
            $textWidth = mb_strwidth($label) + mb_strwidth($command) + mb_strwidth($source);
            $spaceBuffer = 6;

            $dots = str_repeat('.', max(0, $cols - $textWidth - $spaceBuffer));

            // Truncate source if it doesn't fit, but ensure command is always fully visible.
            if ($textWidth + $spaceBuffer > $cols) {
                $availableSourceWidth = max(0, $cols - mb_strwidth($label) - mb_strwidth($command) - mb_strwidth($dots) - $spaceBuffer);
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

        $countSpaces = max(0, $cols - mb_strwidth($countText) - 1);
        $spaces = str_repeat(' ', $countSpaces);

        $this->newLine();
        $this->line($spaces.'<fg=blue;options=bold>'.$countText.'</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
