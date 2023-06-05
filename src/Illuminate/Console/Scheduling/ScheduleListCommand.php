<?php

namespace Illuminate\Console\Scheduling;

use Closure;
use Cron\CronExpression;
use DateTimeZone;
use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Terminal;

#[AsCommand(name: 'schedule:list')]
class ScheduleListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:list
        {--timezone= : The timezone that times should be displayed in}
        {--next : Sort the listed tasks by their next due date}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all scheduled tasks';

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

        if ($events->isEmpty()) {
            $this->components->info('No scheduled tasks have been defined.');

            return;
        }

        $terminalWidth = self::getTerminalWidth();

        $expressionSpacing = $this->getCronExpressionSpacing($events);

        $timezone = new DateTimeZone($this->option('timezone') ?? config('app.timezone'));

        $events = $this->sortEvents($events, $timezone);

        $events = $events->map(function ($event) use ($terminalWidth, $expressionSpacing, $timezone) {
            $expression = $this->formatCronExpression($event->expression, $expressionSpacing);

            $command = $event->command ?? '';

            $description = $event->description ?? '';

            if (! $this->output->isVerbose()) {
                $command = str_replace([Application::phpBinary(), Application::artisanBinary()], [
                    'php',
                    preg_replace("#['\"]#", '', Application::artisanBinary()),
                ], $command);
            }

            if ($event instanceof CallbackEvent) {
                if (class_exists($description)) {
                    $command = $description;
                    $description = '';
                } else {
                    $command = 'Closure at: '.$this->getClosureLocation($event);
                }
            }

            $command = mb_strlen($command) > 1 ? "{$command} " : '';

            $nextDueDateLabel = 'Next Due:';

            $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);

            $nextDueDate = $this->output->isVerbose()
                ? $nextDueDate->format('Y-m-d H:i:s P')
                : $nextDueDate->diffForHumans();

            $hasMutex = $event->mutex->exists($event) ? 'Has Mutex › ' : '';

            $dots = str_repeat('.', max(
                $terminalWidth - mb_strlen($expression.$command.$nextDueDateLabel.$nextDueDate.$hasMutex) - 8, 0
            ));

            // Highlight the parameters...
            $command = preg_replace("#(php artisan [\w\-:]+) (.+)#", '$1 <fg=yellow;options=bold>$2</>', $command);

            return [sprintf(
                '  <fg=yellow>%s</>  %s<fg=#6C7280>%s %s%s %s</>',
                $expression,
                $command,
                $dots,
                $hasMutex,
                $nextDueDateLabel,
                $nextDueDate
            ), $this->output->isVerbose() && mb_strlen($description) > 1 ? sprintf(
                '  <fg=#6C7280>%s%s %s</>',
                str_repeat(' ', mb_strlen($expression) + 2),
                '⇁',
                $description
            ) : ''];
        });

        $this->line(
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
        $rows = $events->map(fn ($event) => array_map('mb_strlen', preg_split("/\s+/", $event->expression)));

        return collect($rows[0] ?? [])->keys()->map(fn ($key) => $rows->max($key))->all();
    }

    /**
     * Sorts the events by due date if option set.
     *
     * @param  \Illuminate\Support\Collection  $events
     * @param  \DateTimeZone  $timezone
     * @return \Illuminate\Support\Collection
     */
    private function sortEvents(\Illuminate\Support\Collection $events, DateTimeZone $timezone)
    {
        return $this->option('next')
                    ? $events->sortBy(fn ($event) => $this->getNextDueDateForEvent($event, $timezone))
                    : $events;
    }

    /**
     * Get the next due date for an event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  \DateTimeZone  $timezone
     * @return \Illuminate\Support\Carbon
     */
    private function getNextDueDateForEvent($event, DateTimeZone $timezone)
    {
        return Carbon::instance(
            (new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now()->setTimezone($event->timezone))
                ->setTimezone($timezone)
        );
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
        $expressions = preg_split("/\s+/", $expression);

        return collect($spacing)
            ->map(fn ($length, $index) => str_pad($expressions[$index], $length))
            ->implode(' ');
    }

    /**
     * Get the file and line number for the event closure.
     *
     * @param  \Illuminate\Console\Scheduling\CallbackEvent  $event
     * @return string
     */
    private function getClosureLocation(CallbackEvent $event)
    {
        $callback = (new ReflectionClass($event))->getProperty('callback')->getValue($event);

        if ($callback instanceof Closure) {
            $function = new ReflectionFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace($this->laravel->basePath().DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
                $function->getStartLine()
            );
        }

        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;

            return sprintf('%s::%s', $className, $callback[1]);
        }

        return sprintf('%s::__invoke', $callback::class);
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
