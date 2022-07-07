<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\terminal;
use Throwable;

class Task extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $description
     * @param  (callable(): bool)|null  $task
     * @param  int  $verbosity
     * @return void
     */
    public static function render($output, $description, $task = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $component = static::fromOutput($output);

        $description = $component->mutate($description, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $task = $task ?: fn () => true;

        $descriptionWidth = mb_strlen(preg_replace("/\<[\w=#\/\;,:.&,%?]+\>|\\e\[\d+m/", '$1', $description) ?? '');

        $output->write("  $description ", false, $verbosity);

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
