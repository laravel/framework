<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Container\Container;
use Illuminate\Console\Scheduling\Event;

class ScheduleListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the scheduled tasks';

    /**
     * The schedule instance.
     *
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Command', 'Expression', 'Maintenance', 'Overlap', 'Output', 'Append'];

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $events = $this->getEvents();

        if (empty($events)) {
            return $this->info("Your application has no scheduled commands to run.");
        }

        $this->displayScheduledEvents($events);
    }

    /**
     * Compile the schedule events into a displayable format.
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = collect($this->schedule->events($this->laravel))->map(function ($event) {
            return $this->getEventInformation($event);
        })->all();

        return $events;
    }

    /**
     * Get the event information.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return array
     */
    protected function getEventInformation(Event $event)
    {
        return [
            'command'     => $this->getEventCommandName($event),
            'expression'  => $event->getExpression(),
            'maintenance' => $event->evenInMaintenanceMode ? 'Execute' : 'Skip',
            'overlapping' => $event->withoutOverlapping ? 'Prevent' : 'Allow',
            'output'      => $this->getEventCommandOutput($event),
            'append'      => $this->getEventAppend($event),
        ];
    }

    /**
     * Get the formated event's command name.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function getEventCommandName(Event $event)
    {
        if(is_null($event->command) && property_exists($event, 'callback')) {
            return 'Closure';
        }

        return implode(' ', array_slice(explode(' ', $event->command), 2));
    }

    /**
     * Get the formated event's command output.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function getEventCommandOutput(Event $event)
    {
        if($this->eventEmailsOutput($event)) {
            return implode(',', $event->emailOutputAddresses);
        }

        return ($event->output != $event->getDefaultOutput())
                    ? $this->removeBasePath($event->output)
                    : '';
    }

    /**
     * Get the formated event's append value.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function getEventAppend(Event $event)
    {
        if($this->eventEmailsOutput($event)) {
            return 'Email';
        }

        return $event->shouldAppendOutput ? 'Yes' : 'No';
    }

    /**
     * Check if the event emails the output.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return bool
     */
    protected function eventEmailsOutput(Event $event)
    {
        return ! empty($event->emailOutputAddresses);
    }

    /**
     * Remove the project's base path from a string.
     *
     * @param  string  $output
     * @return string
     */
    protected function removeBasePath(String $output)
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', base_path($output));
    }

    /**
     * Display the event information on the console.
     *
     * @param  array  $events
     * @return void
     */
    protected function displayScheduledEvents(array $events)
    {
        $this->table($this->headers, $events);
    }
}
