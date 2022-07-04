<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\terminal;
use Throwable;

class Task
{
    use Concerns\EnsureNoPunctuation,
        Concerns\EnsureRelativePaths,
        Concerns\Highlightable;

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
        $description = self::ensureRelativePaths($description);
        $description = self::ensureNoPunctuation($description);
        $description = static::highlightDynamicContent($description);

        $descriptionWidth = mb_strlen(preg_replace("/\<[\w=#\/\;,:.&,%?]+\>|\\e\[\d+m/", '$1', $description) ?? '');

        $output->write("  $description ", false, $verbosity);

        if (is_null($task)) {
            $dots = max(terminal()->width() - $descriptionWidth - 5, 0);

            return $output->write(str_repeat('<fg=gray>.</>', $dots), false, $verbosity);
        }

        $startTime = microtime(true);

        $result = false;

        try {
            $result = $task();
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $runTime = (microtime(true) - $startTime) > 0.05
                ? (' '.number_format((microtime(true) - $startTime) * 1000, 2).'ms')
                : '';

            $runTimeWidth = mb_strlen($runTime);
            $dots = max(terminal()->width() - $descriptionWidth - $runTimeWidth - 10, 0);
            $output->write(str_repeat('<fg=gray>.</>', $dots), false, $verbosity);
            $output->write("<fg=gray>$runTime</>", false, $verbosity);

            $output->writeln(
                $result !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>',
                $verbosity,
            );
        }
    }
}
