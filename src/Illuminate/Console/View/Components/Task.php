<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Database\Migrations\MigrationResult;
use Illuminate\Support\InteractsWithTime;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Termwind\terminal;

class Task extends Component
{
    use InteractsWithTime;

    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $description
     * @param  (callable(): bool)|null  $task
     * @param  int  $verbosity
     * @return void
     */
    public function render($description, $task = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $description = $this->mutate($description, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $descriptionWidth = mb_strlen(preg_replace("/\<[\w=#\/\;,:.&,%?]+\>|\\e\[\d+m/", '$1', $description) ?? '');

        $this->output->write("  $description ", false, $verbosity);

        $startTime = microtime(true);

        $result = MigrationResult::Failure;

        try {
            $result = ($task ?: fn () => MigrationResult::Success)();
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $runTime = $task
                ? (' '.$this->runTimeForHumans($startTime))
                : '';

            $runTimeWidth = mb_strlen($runTime);
            $width = min(terminal()->width(), 150);
            $dots = max($width - $descriptionWidth - $runTimeWidth - 10, 0);

            $this->output->write(str_repeat('<fg=gray>.</>', $dots), false, $verbosity);
            $this->output->write("<fg=gray>$runTime</>", false, $verbosity);

            $this->output->writeln(
                match ($result) {
                    MigrationResult::Failure => ' <fg=red;options=bold>FAIL</>',
                    MigrationResult::Skipped => ' <fg=yellow;options=bold>SKIPPED</>',
                    default => ' <fg=green;options=bold>DONE</>'
                },
                $verbosity,
            );
        }
    }
}
