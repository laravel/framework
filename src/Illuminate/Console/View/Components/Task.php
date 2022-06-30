<?php

namespace Illuminate\Console\View\Components;

use function Termwind\terminal;

class Task
{
    use Concerns\Highlightable;

    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $description
     * @param  (callable(): bool)|null  $task
     * @param  int  $verbosity
     * @return void
     */
    public static function renderUsing($output, $description, $task, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        // if (! $output->isDecorated()) {
        //     $output->write($description);

        //     if ($task) {
        //         $output->writeln(': '.($task() ? 'DONE' : 'FAIL'));
        //     }

        //     return;
        // }

        $descriptionWidth = mb_strlen($description);
        $description = static::highlightDynamicContent($description);
        $output->write("  $description ", false, $verbosity);
        $dots = max(terminal()->width() - $descriptionWidth - 10, 0);
        $output->write(str_repeat('<fg=gray>.</>', $dots), false, $verbosity);

        if (is_null($task)) {
            return $output->writeln(str_repeat('<fg=gray>.</>', 5), $verbosity);
        }

        $output->writeln(
            $task() !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>',
            $verbosity,
        );
    }
}
