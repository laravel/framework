<?php namespace Illuminate\Console\Scheduling;

use Closure;
use Carbon\Carbon;
use LogicException;
use Cron\CronExpression;
use Illuminate\Contracts\Mail\Mailer;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;

class Event {

	/**
	 * The command string.
	 *
	 * @var string
	 */
	protected $command;

	/**
	 * The cron expression representing the event's frequency.
	 *
	 * @var string
	 */
	protected $expression = '* * * * * *';

	/**
	 * The timezone the date should be evaluated on.
	 *
	 * @var \DateTimeZone|string
	 */
	protected $timezone;

	/**
	 * The user the command should run as.
	 *
	 * @var string
	 */
	protected $user;

	/**
	 * The list of environments the command should run under.
	 *
	 * @var array
	 */
	protected $environments = [];

	/**
	 * Indicates if the command should run in maintenance mode.
	 *
	 * @var bool
	 */
	protected $evenInMaintenanceMode = false;

	/**
	 * The filter callback.
	 *
	 * @var \Closure
	 */
	protected $filter;

	/**
	 * The reject callback.
	 *
	 * @var \Closure
	 */
	protected $reject;

	/**
	 * The location that output should be sent to.
	 *
	 * @var string
	 */
	protected $output = '/dev/null';

	/**
	 * The e-mail addresses the command output should be sent to.
	 *
	 * @var array
	 */
	protected $emailAddresses = [];

	/**
	 * The callback to be run after the event is finished.
	 *
	 * @var \Closure
	 */
	protected $afterCallback;

	/**
	 * The human readable description of the event.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Create a new event instance.
	 *
	 * @param  string  $command
	 * @return void
	 */
	public function __construct($command)
	{
		$this->command = $command;
	}

	/**
	 * Run the given event.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	public function run(Container $container)
	{
		if ($this->afterCallback || ! empty($this->emailAddresses))
		{
			$this->runCommandInForeground($container);
		}
		else
		{
			$this->runCommandInBackground();
		}
	}

	/**
	 * Run the command in the background using exec.
	 *
	 * @return void
	 */
	protected function runCommandInBackground()
	{
		chdir(base_path());

		exec($this->buildCommand());
	}

	/**
	 * Run the command in the foreground.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	protected function runCommandInForeground(Container $container)
	{
		(new Process(
			trim($this->buildCommand(), '& '), base_path(), null, null, null
		))->run();

		if ($this->afterCallback)
		{
			$container->call($this->afterCallback);
		}

		if ($this->emailAddresses && $container->bound('Illuminate\Contracts\Mail\Mailer'))
		{
			$this->emailOutput($container->make('Illuminate\Contracts\Mail\Mailer'));
		}
	}

	/**
	 * E-mail the output of the event to the recipients.
	 *
	 * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
	 * @return void
	 */
	protected function emailOutput(Mailer $mailer)
	{
		$mailer->raw(file_get_contents($this->output), function($m)
		{
			$m->subject($this->getEmailSubject());

			foreach ($this->emailAddresses as $address)
			{
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
		if ($this->description)
		{
			return 'Scheduled Job Output ('.$this->description.')';
		}

		return 'Scheduled Job Output';
	}

	/**
	 * Build the comand string.
	 *
	 * @return string
	 */
	public function buildCommand()
	{
		$command = $this->command.' > '.$this->output.' 2>&1 &';

		return $this->user ? 'sudo -u '.$this->user.' '.$command : $command;
	}

	/**
	 * Determine if the given event should run based on the Cron expression.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return bool
	 */
	public function isDue(Application $app)
	{
		if ($app->isDownForMaintenance() && ! $this->runsInMaintenanceMode())
		{
			return false;
		}

		return $this->expressionPasses() &&
			   $this->filtersPass($app) &&
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

		if ($this->timezone)
		{
			$date->setTimezone($this->timezone);
		}

		return CronExpression::factory($this->expression)->isDue($date);
	}

	/**
	 * Determine if the filters pass for the event.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return bool
	 */
	protected function filtersPass(Application $app)
	{
		if (($this->filter && ! $app->call($this->filter)) ||
			 $this->reject && $app->call($this->reject))
		{
			return false;
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
		$this->expression = '0 * * * * *';

		return $this;
	}

	/**
	 * Schedule the event to run daily.
	 *
	 * @return $this
	 */
	public function daily()
	{
		$this->expression = '0 0 * * * *';

		return $this;
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
	 * @return $this
	 */
	public function twiceDaily()
	{
		$this->expression = '0 1,13 * * * *';

		return $this;
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
		$this->expression = '0 0 * * 0 *';

		return $this;
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
		$this->expression = '0 0 1 * * *';

		return $this;
	}

	/**
	 * Schedule the event to run yearly.
	 *
	 * @return $this
	 */
	public function yearly()
	{
		$this->expression = '0 0 1 1 * *';

		return $this;
	}

	/**
	 * Schedule the event to run every five minutes.
	 *
	 * @return $this
	 */
	public function everyFiveMinutes()
	{
		$this->expression = '*/5 * * * * *';

		return $this;
	}

	/**
	 * Schedule the event to run every ten minutes.
	 *
	 * @return $this
	 */
	public function everyTenMinutes()
	{
		$this->expression = '*/10 * * * * *';

		return $this;
	}

	/**
	 * Schedule the event to run every thirty minutes.
	 *
	 * @return $this
	 */
	public function everyThirtyMinutes()
	{
		$this->expression = '0,30 * * * * *';

		return $this;
	}

	/**
	 * Set the days of the week the command should run on.
	 *
	 * @param  array|dynamic  $days
	 * @return $this
	 */
	public function days($days)
	{
		$this->spliceIntoPosition(5, implode(',', is_array($days) ? $days : func_get_args()));

		return $this;
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
	 * @param  array|dynamic  $environments
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
	 * Register a callback to further filter the schedule.
	 *
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function when(Closure $callback)
	{
		$this->filter = $callback;

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
		$this->reject = $callback;

		return $this;
	}

	/**
	 * Send the output of the command to a given location.
	 *
	 * @param  string  $location
	 * @return $this
	 */
	public function sendOutputTo($location)
	{
		$this->output = $location;

		return $this;
	}

	/**
	 * E-mail the results of the scheduled operation.
	 *
	 * @param  array|dynamic  $addresses
	 * @return $this
	 *
	 * @throws \LogicException
	 */
	public function emailOutputTo($addresses)
	{
		if (is_null($this->output) || $this->output == '/dev/null')
		{
			throw new LogicException("Must direct output to a file in order to e-mail results.");
		}

		$this->emailAddresses = is_array($addresses) ? $addresses : func_get_args();

		return $this;
	}

	/**
	 * Register a callback to be called after the operation.
	 *
	 * @param  \Closure  $callback
	 * @return $this
	 */
	public function then(Closure $callback)
	{
		$this->afterCallback = $callback;

		return $this;
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
	 * @return void
	 */
	protected function spliceIntoPosition($position, $value)
	{
		$segments = explode(' ', $this->expression);

		$segments[$position - 1] = $value;

		$this->expression = implode(' ', $segments);

		return $this;
	}

	/**
	 * Get the summary of the event for display.
	 *
	 * @return string
	 */
	public function getSummaryForDisplay()
	{
		if (is_string($this->description)) return $this->description;

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
