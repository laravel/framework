<?php

namespace Illuminate\Console\Scheduling;

use Cron\CronExpression;
use DateTimeZone;
use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Terminal;

class ScheduleListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:list {--timezone= : The timezone that times should be displayed in}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the scheduled commands';

    /**
     * The terminal width resolver callback.
     *
     * @var \Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     *
     * @throws \Exception
     */
    public function handle(Schedule $schedule)
    {
        $events = collect($schedule->events());
        $terminalWidth = $this->getTerminalWidth();
        $expressionSpacing = $this->getCronExpressionSpacing($events);

        $events = $events->map(function ($event) use ($terminalWidth, $expressionSpacing) {
            $expression = $this->formatCronExpression($event->expression, $expressionSpacing);

            $command = $event->command;

            if (! $this->output->isVerbose()) {
                $command = str_replace(
                    Application::artisanBinary(),
                    preg_replace("#['\"]#", '', Application::artisanBinary()),
                    str_replace(Application::phpBinary(), 'php', $event->command)
                );
            }

            $command = mb_strlen($command) > 1 ? "{$command} " : '';

            $nextDueDateLabel = 'Next Due:';

            $nextDueDate = Carbon::create((new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now()->setTimezone($event->timezone))
                ->setTimezone(new DateTimeZone($this->option('timezone') ?? config('app.timezone')))
            );

            $nextDueDate = $this->output->isVerbose()
                ? $nextDueDate->format('Y-m-d H:i:s P')
                : $nextDueDate->diffForHumans();

            $hasMutex = $event->mutex->exists($event) ? 'Has Mutex › ' : '';

            $dots = str_repeat('.', max(
                $terminalWidth - mb_strlen($expression.$command.$nextDueDateLabel.$nextDueDate.$hasMutex) - 8, 0
            ));

            // Highlight the parameters...
            $command = preg_replace("#(=['\"]?)([^'\"]+)(['\"]?)#", '$1<fg=yellow;options=bold>$2</>$3', $command);

            return [sprintf(
                '  <fg=yellow>%s</>  %s<fg=#6C7280>%s %s%s %s</>',
                $expression,
                $command,
                $dots,
                $hasMutex,
                $nextDueDateLabel,
                $nextDueDate
            ), $this->output->isVerbose() && mb_strlen($event->description) > 1 ? sprintf(
                '  <fg=#6C7280>%s%s %s</>',
                str_repeat(' ', mb_strlen($expression) + 2),
                '⇁',
                $event->description
            ) : ''];
        });

        if ($events->isEmpty()) {
            return $this->comment('No scheduled tasks have been defined.');
        }

        $this->output->writeln(
            $events->flatten()->filter()->prepend('')->push('')->toArray()
        );
    }

    /**
     * Gets the spacing to be used on each event row.
     *
     * @param  \Illuminate\Support\Collection  $events
     * @return array<int, int>
     */
    private function getCronExpressionSpacing($events)
    {
        $rows = $events->map(fn ($event) => array_map('mb_strlen', explode(' ', $event->expression)));

        return collect($rows[0] ?? [])->keys()->map(fn ($key) => $rows->max($key));
    }

    /**
     * Formats the cron expression based on the spacing provided.
     *
     * @param  string  $expression
     * @param  array<int, int>  $spacing
     * @return string
     */
    private function formatCronExpression($expression, $spacing)
    {
        $expression = explode(' ', $expression);

        return collect($spacing)
            ->map(fn ($length, $index) => $expression[$index] = str_pad($expression[$index], $length))
            ->implode(' ');
    }

    /**
     * Get the terminal width.
     *
     * @return int
     */
    public static function getTerminalWidth()
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }

    /**
     * Set a callback that should be used when resolving the terminal width.
     *
     * @param  \Closure|null  $resolver
     * @return void
     */
    public static function resolveTerminalWidthUsing($resolver)
    {
        static::$terminalWidthResolver = $resolver;
    }
}
