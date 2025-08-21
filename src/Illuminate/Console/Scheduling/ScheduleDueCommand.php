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

#[AsCommand(name: 'schedule:due')]
class ScheduleDueCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:due
        {datetime : The date/time to check scheduled tasks for (YYYY-MM-DD HH:MM format or YYYY-MM-DD)}
        {--timezone= : The timezone that times should be displayed in}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check which scheduled tasks are due to run at a given date/time';

    /**
     * The terminal width resolver callback.
     *
     * @var \Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * Execute the console command.
     *
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

        $datetimeInput = $this->argument('datetime');

        $timezone = new DateTimeZone($this->option('timezone') ?? config('app.timezone'));

        $targetDatetime = $this->parseDateTime($datetimeInput, $timezone);

        if (! $targetDatetime) {
            $this->components->error('Invalid date/time format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM (e.g., 2024-01-15 or 2024-01-15 14:30)');

            return;
        }

        $targetDatetime = $targetDatetime->setTimezone($timezone);

        $matchingEvents = $events->filter(function ($event) use ($targetDatetime) {
            return $this->isEventDue($event, $targetDatetime);
        });

        if ($matchingEvents->isEmpty()) {
            $this->components->info("No scheduled tasks are due to run at: {$targetDatetime->format('Y-m-d H:i')} ({$timezone->getName()})");

            return;
        }

        $this->components->info("The following scheduled tasks are due to run at: {$targetDatetime->format('Y-m-d H:i')} ({$timezone->getName()})");

        $terminalWidth = self::getTerminalWidth();
        $expressionSpacing = $this->getCronExpressionSpacing($matchingEvents);
        $repeatExpressionSpacing = $this->getRepeatExpressionSpacing($matchingEvents);

        $matchingEvents = $matchingEvents->map(function ($event) use ($terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone) {
            return $this->listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone);
        });

        $this->line(
            $matchingEvents->flatten()->filter()->prepend('')->push('')->toArray()
        );
    }

    /**
     * Parse the datetime input string.
     *
     * @param  string  $input
     * @return \Illuminate\Support\Carbon|null
     */
    private function parseDateTime($input, $timezone)
    {
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input)) {
                return Carbon::createFromFormat('Y-m-d H:i', $input, $timezone);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
                return Carbon::createFromFormat('Y-m-d', $input)->startOfDay();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if an event is due at the given datetime.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    private function isEventDue($event, Carbon $datetime)
    {
        try {
            $cronExpression = new CronExpression($event->expression);

            $checkTime = $datetime->copy();

            if ($event->timezone) {
                $checkTime = $checkTime->setTimezone($event->timezone);
            }

            return $cronExpression->isDue($checkTime);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the spacing to be used on each event row.
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
     * Get the spacing to be used on each event row.
     *
     * @param  \Illuminate\Support\Collection  $events
     * @return int
     */
    private function getRepeatExpressionSpacing($events)
    {
        return $events->map(fn ($event) => mb_strlen($this->getRepeatExpression($event)))->max();
    }

    /**
     * List the given event in the console.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  int  $terminalWidth
     * @param  array  $expressionSpacing
     * @param  int  $repeatExpressionSpacing
     * @param  \DateTimeZone  $timezone
     * @return array
     */
    private function listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone)
    {
        $expression = $this->formatCronExpression($event->expression, $expressionSpacing);

        $repeatExpression = str_pad($this->getRepeatExpression($event), $repeatExpressionSpacing);

        $command = $event->command ?? '';

        $description = $event->description ?? '';

        if (! $this->output->isVerbose()) {
            $command = str_replace([Application::phpBinary(), Application::artisanBinary()], [
                'php',
                preg_replace("#['\"]#", '', Application::artisanBinary()),
            ], $command);
        }

        if ($event instanceof CallbackEvent) {
            $command = $event->getSummaryForDisplay();

            if (in_array($command, ['Closure', 'Callback'])) {
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
            $terminalWidth - mb_strlen($expression.$repeatExpression.$command.$nextDueDateLabel.$nextDueDate.$hasMutex) - 8,
            0
        ));

        $command = preg_replace("#(php artisan [\w\-:]+) (.+)#", '$1 <fg=yellow;options=bold>$2</>', $command);

        return [sprintf(
            '  <fg=yellow>%s</> <fg=#6C7280>%s</> %s<fg=#6C7280>%s %s%s %s</>',
            $expression,
            $repeatExpression,
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
    }

    /**
     * Get the repeat expression for an event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    private function getRepeatExpression($event)
    {
        return $event->isRepeatable() ? "{$event->repeatSeconds}s " : '';
    }

    /**
     * Get the next due date for an event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return \Illuminate\Support\Carbon
     */
    private function getNextDueDateForEvent($event, DateTimeZone $timezone)
    {
        $nextDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now()->setTimezone($event->timezone))
                ->setTimezone($timezone)
        );

        if (! $event->isRepeatable()) {
            return $nextDueDate;
        }

        $previousDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getPreviousRunDate(Carbon::now()->setTimezone($event->timezone), allowCurrentDate: true)
                ->setTimezone($timezone)
        );

        $now = Carbon::now()->setTimezone($event->timezone);

        if (! $now->copy()->startOfMinute()->eq($previousDueDate)) {
            return $nextDueDate;
        }

        return $now
            ->endOfSecond()
            ->ceilSeconds($event->repeatSeconds);
    }

    /**
     * Format the cron expression based on the spacing provided.
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
