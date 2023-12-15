<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Closure;
use DateTime;
use Exception;
use Carbon\Carbon;
use Cron\CronExpression;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Console\Application;
use Symfony\Component\Console\Terminal;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleCalendarCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:calendar
                            {--date=today : Range of calendar to display (today, yyyy-mm-dd)}
                            {--range=day : Range of calendar to display (day, week)}
                            {--hoursPerLine=12 : Number of hours per line (1, 2, 3, 4, 6, 8, 12, 24)}
                            {--display=count : Display type (dot, count, list)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display scheduled tasks in calendar view';

    /**
     * The terminal width resolver callback.
     *
     * @var Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * The number of hours per calendar line.
     */
    protected int $hoursPerLine;

    /**
     * The number of characters per one hour field.
     */
    protected int $hourWidth;

    /**
     * Real amount of minutes inside one displayed minute character.
     */
    protected float $minutesPerField;

    /**
     * Display type of scheduled tasks (dot, count, list).
     */
    protected string $display;

    /**
     * List of symbols with associated command.
     */
    protected array $commands;

    /**
     * Execute the console command.
     *
     * @param  Schedule  $schedule
     *
     * @throws Exception
     */
    public function handle(Schedule $schedule): void
    {
        $date = $this->option('date');
        if ($date === 'today') {
            $date = today();
        } elseif (preg_match('#^\d{4}-\d{2}-\d{2}$#', $date)) {
            $date = Carbon::parse($date);
        } else {
            $this->error('Date must be "today" or date in format "yyyy-mm-dd".');
            return;
        }

        $range = $this->option('range');
        if (!in_array($range, ['day', 'week'], true)) {
            $this->error('Range must be one of "day" or "week".');
            return;
        }

        $hoursPerLine = $this->option('hoursPerLine');
        if (!in_array($hoursPerLine, [1, 2, 3, 4, 6, 8, 12, 24], false)) {
            $this->error('Hours per line must be one of 1, 2, 3, 4, 6, 8, 12, 24.');
            return;
        }
        $this->hoursPerLine = (int) $hoursPerLine;

        $display = $this->option('display');
        if (!in_array($display, ['dot', 'count', 'list'], true)) {
            $this->error('Display must be one of "dot", "count", "list".');
            return;
        }
        $this->display = $display;

        $terminalWidth = self::getTerminalWidth();

        $this->hourWidth = (int) (($terminalWidth - 1) / $this->hoursPerLine);
        $this->minutesPerField = 60 / ($this->hourWidth - 1);
        while ($this->hourWidth < 7) {
            $this->hoursPerLine /= 2;
            $this->error('Terminal width is too small. Hours per line adjusted to '.$this->hoursPerLine.'.');
            $this->hourWidth = (int) (($terminalWidth - 1) / $this->hoursPerLine);
            $this->minutesPerField = 60 / ($this->hourWidth - 1);
        }

        $start = match ($range) {
            'week' => $date->copy()->startOfWeek(),
            default => $date->copy()->startOfDay()
        };

        $end = match ($range) {
            'week' => $date->copy()->endOfWeek(),
            default => $date->copy()->endOfDay()
        };

        $period = new CarbonPeriod($start, '1 day', $end);

        $defaultArray = $this->preparyDatetimesArray($period);

        $scheduledTasks = $this->mapTasks($schedule, $defaultArray, $start, $end);

        $this->printCalendar($scheduledTasks, $period);
    }

    /**
     * Prepare array of datetimes for calendar.
     */
    private function preparyDatetimesArray(CarbonPeriod $period): array
    {
        $array = [];
        /** @var Carbon $day */
        foreach ($period as $day) {
            for ($hour = 0; $hour < 24; $hour++) {
                for ($minutes = 0; $minutes < $this->hourWidth - 1; $minutes++) {
                    $fieldStart = $day->copy()
                        ->addHours($hour)
                        ->addMinutes($minutes * (int) $this->minutesPerField)
                        ->addSeconds($minutes * (int) (60 * ($this->minutesPerField - (int) $this->minutesPerField)));
                    $array[$day->toDateString()][$hour][$fieldStart->toDateTimeString()] = [];
                }
            }
        }
        return $array;
    }

    /**
     * Map scheduled tasks to datetimes array.
     * @throws Exception
     */
    private function mapTasks(Schedule $schedule, array $defaultArray, Carbon $start, Carbon $end): array
    {
        $events = collect($schedule->events());

        $symbols = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));

        for ($i = 0, $iMax = count($events); $i < $iMax; $i++) {
            $command = str_replace([Application::phpBinary(), Application::artisanBinary()], [
                'php',
                preg_replace("#['\"]#", '', Application::artisanBinary()),
            ], $events[$i]->command);
            $this->commands[$symbols[$i]] = $command;

            $cronExpression = new CronExpression($events[$i]->expression);

            $nextRunDate = $cronExpression->getNextRunDate($start, 0, true);
            while ($nextRunDate <= $end) {
                $dateString = $nextRunDate->format('Y-m-d');
                $hourString = $nextRunDate->format('G');
                $prevKey = key($defaultArray[$dateString][$hourString]);
                end($defaultArray[$dateString][$hourString]);
                $lastKey = key($defaultArray[$dateString][$hourString]);
                foreach ($defaultArray[$dateString][$hourString] as $key => $value) {
                    if (new DateTime($key) > $nextRunDate) {
                        $defaultArray[$dateString][$hourString][$prevKey]['symbols'][] = $symbols[$i];
                        break;
                    }
                    $prevKey = $key;
                }
                if ($prevKey === $lastKey) {
                    $defaultArray[$dateString][$hourString][$prevKey]['symbols'][] = $symbols[$i];
                }
                $nextRunDate = Carbon::parse($cronExpression->getNextRunDate($nextRunDate, 1, true));
            }
        }
        return $defaultArray;
    }

    /**
     * Print calendar.
     */
    private function printCalendar(array $scheduledTasks, CarbonPeriod $days): void
    {
        if ($this->display === 'list') {
            $this->line(str_pad('Legend', ($this->hourWidth * $this->hoursPerLine + 1), ' ', STR_PAD_BOTH), 'bg=blue;fg=bright-white');
            foreach ($this->commands as $symbol => $command) {
                $this->components->twoColumnDetail($command, '<fg=red>'.$symbol.'</>');
            }
            $this->line('');
        }

        /** @var Carbon $day */
        foreach ($days as $day) {
            $dayString = $day->toDateString();
            $this->line(str_pad($day->format('l Y-m-d'), ($this->hourWidth * $this->hoursPerLine + 1), ' ', STR_PAD_BOTH), 'bg=blue;fg=bright-white');
            $hour = today();
            for ($lines = 0, $totalLines = 24 / $this->hoursPerLine; $lines < $totalLines; $lines++) {
                for ($hours = 0; $hours < $this->hoursPerLine; $hours++) {
                    $this->output->write($hour->format('H:i'));
                    $this->output->write(str_repeat(' ', $this->hourWidth - ($hours === 0 ? 7 : 5)));
                    $hour = $hour->addHour();
                }
                $this->output->newLine();
                $linesArray = [];
                for ($hours = $lines * $this->hoursPerLine, $maxHour = $lines * $this->hoursPerLine + $this->hoursPerLine; $hours < $maxHour; $hours++) {
                    $linesArray[0][] = ['value' => '|'];
                    for ($minutes = 0; $minutes < $this->hourWidth - 1; $minutes++) {
                        $fieldStart = $day->copy()
                            ->addHours($hours)
                            ->addMinutes($minutes * (int) $this->minutesPerField)
                            ->addSeconds($minutes * (int) (60 * ($this->minutesPerField - (int) $this->minutesPerField)))
                            ->toDateTimeString();
                        $scheduledTask = $scheduledTasks[$dayString][$hours][$fieldStart] ?? [];
                        $minutesFieldCharacter = match ($this->display) {
                            'dot' => !empty($scheduledTask['symbols']) ? ['value' => '•', 'style' => '<fg=red>'] : ['value' => '-'],
                            'count' => !empty($scheduledTask['symbols']) ? ['value' => count($scheduledTask['symbols']), 'style' => '<fg=red>'] : ['value' => '-'],
                            'list' => !empty($scheduledTask['symbols']) ? ['value' => implode('', $scheduledTask['symbols']), 'style' => '<fg=red>'] : ['value' => '-'],
                            default => ['value' => '-'],
                        };
                        $linesArray[0][] = $minutesFieldCharacter;
                    }
                }
                $linesArray[0][] = ['value' => '|'];

                $maxValueLength = 1;
                if ($this->display !== 'dot') {
                    foreach ($linesArray[0] as $record) {
                        $length = strlen((string) $record['value']);
                        $maxValueLength = max($length, $maxValueLength);
                    }
                }

                foreach ($linesArray[0] as $key => $record) {
                    for ($i = 0; $i < $maxValueLength; $i++) {
                        $styleStart = $record['style'] ?? '';
                        $styleEnd = $record['style'] ?? null ? '</>' : '';
                        $character = mb_strlen((string) $record['value']) > $i ? mb_substr((string) $record['value'], $i, 1) : ' ';
                        $linesArray[$i][$key] = $styleStart.$character.$styleEnd;
                    }
                }
                foreach ($linesArray as $linevalue) {
                    $this->output->writeln(implode('', (array) $linevalue));
                }
            }
        }
    }

    /**
     * Get the terminal width.
     */
    public static function getTerminalWidth(): int
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }
}
