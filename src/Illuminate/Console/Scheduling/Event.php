<?php

namespace Illuminate\Console\Scheduling;

use Closure;
use Carbon\Carbon;
use LogicException;
use Cron\CronExpression;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Mail\Mailer;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Illuminate\Contracts\Container\Container;

class Event
{
    /**
     * The command string.
     *
     * @var string
     */
    public $command;

    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    public $expression = '* * * * * *';

    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone|string
     */
    public $timezone;

    /**
     * The user the command should run as.
     *
     * @var string
     */
    public $user;

    /**
     * The list of environments the command should run under.
     *
     * @var array
     */
    public $environments = [];

    /**
     * Indicates if the command should run in maintenance mode.
     *
     * @var bool
     */
    public $evenInMaintenanceMode = false;

    /**
     * Indicates if the command should not overlap itself.
     *
     * @var bool
     */
    public $withoutOverlapping = false;

    /**
     * The array of filter callbacks.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * The array of reject callbacks.
     *
     * @var array
     */
    protected $rejects = [];

    /**
     * The location that output should be sent to.
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * Indicates whether output should be appended.
     *
     * @var bool
     */
    protected $shouldAppendOutput = false;

    /**
     * The array of callbacks to be run before the event is started.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the event is finished.
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * The human readable description of the event.
     *
     * @var string
     */
    public $description;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @return void
     */
    public function __construct($command)
    {
        $this->command = $command;
        $this->output = $this->getDefaultOutput();
    }

    /**
     * Get the default output depending on the OS.
     *
     * @return string
     */
    protected function getDefaultOutput()
    {
        return (DIRECTORY_SEPARATOR == '\\') ? 'NUL' : '/dev/null';
    }

    /**
     * Run the given event.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function run(Container $container)
    {
        $this->runCommandInForeground($container);
    }

    /**
     * Run the command in the foreground.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function runCommandInForeground(Container $container)
    {
        $this->callBeforeCallbacks($container);

        (new Process(
            trim($this->buildCommand(), '& '), base_path(), null, null, null
        ))->run();

        $this->callAfterCallbacks($container);
    }

    /**
     * Call all of the "before" callbacks for the event.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function callBeforeCallbacks(Container $container)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Call all of the "after" callbacks for the event.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function callAfterCallbacks(Container $container)
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function buildCommand()
    {
        $output = ProcessUtils::escapeArgument($this->output);

        $redirect = $this->shouldAppendOutput ? ' >> ' : ' > ';

        if ($this->withoutOverlapping) {
            $command = '(touch '.$this->mutexPath().'; '.$this->command.'; rm '.$this->mutexPath().')'.$redirect.$output.' 2>&1 &';
        } else {
            $command = $this->command.$redirect.$output.' 2>&1 &';
        }

        return $this->user ? 'sudo -u '.$this->user.' '.$command : $command;
    }

    /**
     * Get the mutex path for the scheduled command.
     *
     * @return string
     */
    protected function mutexPath()
    {
        return storage_path('framework/schedule-'.sha1($this->expression.$this->command));
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function isDue($app)
    {
        if (! $this->runsInMaintenanceMode() && $app->isDownForMaintenance()) {
            return false;
        }

        return $this->expressionPasses() &&
               $this->runsInEnvironment($app->environment());
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function filtersPass($app)
    {
        foreach ($this->filters as $callback) {
            if (! $app->call($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if ($app->call($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the event runs in the given environment.
     *
     * @param  string  $environment
     * @return bool
     */
    public function runsInEnvironment($environment)
    {
        return empty($this->environments) || in_array($environment, $this->environments);
    }

    /**
     * Determine if the event runs in maintenance mode.
     *
     * @return bool
     */
    public function runsInMaintenanceMode()
    {
        return $this->evenInMaintenanceMode;
    }

    /**
     * The Cron expression representing the event's frequency.
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->cron('0 * * * * *');
    }

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->cron('0 0 * * * *');
    }

    /**
     * Schedule the command at a given time.
     *
     * @param  string  $time
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int) $segments[0])
                    ->spliceIntoPosition(1, count($segments) == 2 ? (int) $segments[1] : '0');
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @param  int  $first
     * @param  int  $second
     * @return $this
     */
    public function twiceDaily($first = 1, $second = 13)
    {
        $hours = $first.','.$second;

        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, $hours);
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->cron('0 0 * * 0 *');
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->cron('0 0 1 * * *');
    }

    /**
     * Schedule the event to run quarterly.
     *
     * @return $this
     */
    public function quarterly()
    {
        return $this->cron('0 0 1 */3 *');
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->cron('0 0 1 1 * *');
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->cron('* * * * * *');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->cron('*/5 * * * * *');
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->cron('*/10 * * * * *');
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->cron('0,30 * * * * *');
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param  array|mixed  $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @param  \DateTimeZone|string  $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Set which user the command should run as.
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Limit the environments the command should run in.
     *
     * @param  array|mixed  $environments
     * @return $this
     */
    public function environments($environments)
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();

        return $this;
    }

    /**
     * State that the command should run even in maintenance mode.
     *
     * @return $this
     */
    public function evenInMaintenanceMode()
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @return $this
     */
    public function withoutOverlapping()
    {
        $this->withoutOverlapping = true;

        return $this->skip(function () {
            return file_exists($this->mutexPath());
        });
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function when(Closure $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * Send the output of the command to a given location.
     *
     * @param  string  $location
     * @param  bool  $append
     * @return $this
     */
    public function sendOutputTo($location, $append = false)
    {
        $this->output = $location;

        $this->shouldAppendOutput = $append;

        return $this;
    }

    /**
     * Append the output of the command to a given location.
     *
     * @param  string  $location
     * @return $this
     */
    public function appendOutputTo($location)
    {
        return $this->sendOutputTo($location, true);
    }

    /**
     * E-mail the results of the scheduled operation.
     *
     * @param  array|mixed  $addresses
     * @param  bool  $onlyIfOutputExists
     * @return $this
     *
     * @throws \LogicException
     */
    public function emailOutputTo($addresses, $onlyIfOutputExists = false)
    {
        if (is_null($this->output) || $this->output == $this->getDefaultOutput()) {
            throw new LogicException('Must direct output to a file in order to e-mail results.');
        }

        $addresses = is_array($addresses) ? $addresses : func_get_args();

        return $this->then(function (Mailer $mailer) use ($addresses, $onlyIfOutputExists) {
            $this->emailOutput($mailer, $addresses, $onlyIfOutputExists);
        });
    }

    /**
     * E-mail the results of the scheduled operation if it produces output.
     *
     * @param  array|mixed  $addresses
     * @return $this
     *
     * @throws \LogicException
     */
    public function emailWrittenOutputTo($addresses)
    {
        return $this->emailOutputTo($addresses, true);
    }

    /**
     * E-mail the output of the event to the recipients.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @param  array  $addresses
     * @param  bool  $onlyIfOutputExists
     * @return void
     */
    protected function emailOutput(Mailer $mailer, $addresses, $onlyIfOutputExists = false)
    {
        $text = file_get_contents($this->output);

        if ($onlyIfOutputExists && empty($text)) {
            return;
        }

        $mailer->raw($text, function ($m) use ($addresses) {
            $m->subject($this->getEmailSubject());

            foreach ($addresses as $address) {
                $m->to($address);
            }
        });
    }

    /**
     * Get the e-mail subject line for output results.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        if ($this->description) {
            return 'Scheduled Job Output ('.$this->description.')';
        }

        return 'Scheduled Job Output';
    }

    /**
     * Register a callback to ping a given URL before the job runs.
     *
     * @param  string  $url
     * @return $this
     */
    public function pingBefore($url)
    {
        return $this->before(function () use ($url) {
            (new HttpClient)->get($url);
        });
    }

    /**
     * Register a callback to be called before the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to ping a given URL after the job runs.
     *
     * @param  string  $url
     * @return $this
     */
    public function thenPing($url)
    {
        return $this->then(function () use ($url) {
            (new HttpClient)->get($url);
        });
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        return $this->then($callback);
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function name($description)
    {
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param  int  $position
     * @param  string  $value
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return $this->buildCommand();
    }

    /**
     * Get the Cron expression for the event.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }
}
