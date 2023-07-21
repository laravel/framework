<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:test')]
class ScheduleTestCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:test {--name= : The name of the scheduled command to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a scheduled command';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        $phpBinary = Application::phpBinary();
        $commands = $schedule->events();
        $commandNames = [];

        foreach ($commands as $command) {
            $commandName = $command->command ?? $command->getSummaryForDisplay();

            $suffix = '';

            if (in_array($commandName, $commandNames)) {
                $suffix = ' (' . count(array_filter($commandNames, fn($command) => $command === $commandName)) . ')';
            }

            $commandNames[] = $commandName . $suffix;
        }

        if (empty($commandNames)) {
            return $this->components->info('No scheduled commands have been defined.');
        }

        if (!empty($name = $this->option('name'))) {
            $commandBinary = $phpBinary.' '.Application::artisanBinary();
            $matches = array_filter($commands, function ($command) use ($commandBinary, $name) {
                return trim(str_replace($commandBinary, '', $command->getSummaryForDisplay())) === $name;
            });

            if (count($matches) > 0) {
                foreach ($matches as $match) {
                    $this->runSingleTask($match, $commandBinary);
                }

                return;
            } else {
                $this->components->info('No matching scheduled command found.');
                return;
            }
        } else {
            $index = array_search($this->components->choice('Which command would you like to run?', $commandNames), $commandNames);
            $this->runSingleTask($commands[$index], $phpBinary);
        }

        $this->newLine();
    }

    /**
     * Runs a single task.
     *
     * This function is responsible for the execution of a single task.
     * It prepares the description of the task, executes it and displays a summary.
     *
     * @param \Illuminate\Console\Scheduling\Event $task
     * @param string $phpBinary
     * @return void
     */
    protected function runSingleTask(\Illuminate\Console\Scheduling\Event $task, string $phpBinary)
    {
        $summary = $task->getSummaryForDisplay();

        $command = $task instanceof CallbackEvent
            ? $summary
            : trim(str_replace($phpBinary, '', $task->command));

        $description = sprintf(
            'Running [%s]%s',
            $command,
            $task->runInBackground ? ' in background' : '',
        );

        $this->components->task($description, fn () => $task->run($this->laravel));

        if (! $task instanceof CallbackEvent) {
            $this->components->bulletList([$task->getSummaryForDisplay()]);
        }

        $this->newLine();
    }
}
