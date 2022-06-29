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
    protected $signature = 'schedule:list {--timezone= : The timezone that times should be displayed in}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'schedule:list';

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

        if ($events->isEmpty()) {
            $this->info('No scheduled tasks have been defined.');

            return;
        }

        $terminalWidth = self::getTerminalWidth();

        $expressionSpacing = $this->getCronExpressionSpacing($events);

        $timezone = new DateTimeZone($this->option('timezone') ?? config('app.timezone'));

        $events = $events->map(function ($event) use ($terminalWidth, $expressionSpacing, $timezone) {
            $expression = $this->formatCronExpression($event->expression, $expressionSpacing);

            $command = $event->command;
            $description = $event->description;

            if (! $this->output->isVerbose()) {
                $command = str_replace([Application::phpBinary(), Application::artisanBinary()], [
                    'php',
                    preg_replace("#['\"]#", '', Application::artisanBinary()),
                ], $event->command);
            }

            if ($event instanceof CallbackEvent) {
                if (class_exists($event->description)) {
                    $command = $event->description;
                    $description = '';
                } else {
                    $command = 'Closure at: '.$this->getClosureLocation($event);
                }
            }

            $command = mb_strlen($command) > 1 ? "{$command} " : '';

            $nextDueDateLabel = 'Next Due:';

            $nextDueDate = Carbon::create((new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now()->setTimezone($event->timezone))
                ->setTimezone($timezone)
            );

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
        $expressions = explode(' ', $expression);

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
        $callback = tap((new ReflectionClass($event))->getProperty('callback'))
                        ->setAccessible(true)
                        ->getValue($event);

        if ($callback instanceof Closure) {
            $function = new ReflectionFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace($this->laravel->basePath().DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
                $function->getStartLine()
            );
        }

        if (is_array($callback)) {
            return sprintf('%s::%s', $callback[0]::class, $callback[1]);
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
