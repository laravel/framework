<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Terminal;

#[AsCommand(name: 'event:list')]
class EventListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:list {--event= : Filter the events by name}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'event:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "List the application's events and listeners";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = $this->getEvents();

        if (empty($events)) {
            return $this->error("Your application doesn't have any events matching the given criteria.");
        }

        $this->line('');
        $this->line('<fg=red> - Total of '.'</>'.count($events).'<fg=red> events enjoy having their listeners:</>');

        $width = (new Terminal)->getWidth();

        $this->printSeparator($width);

        foreach ($events as $event => $listeners) {
            $this->printEvent($event);
            $this->printsListeners($listeners, $width);
            $this->printSeparator($width);
        }

        $this->line('');
    }

    /**
     * Get all of the events and listeners configured for the application.
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = $this->getListenersOnDispatcher();

        if ($this->filteringByEvent()) {
            $events = $this->filterEvents($events);
        }

        return $events;
    }

    /**
     * Get the event / listeners from the dispatcher object.
     *
     * @return array
     */
    protected function getListenersOnDispatcher()
    {
        $events = [];

        foreach ($this->getRawListeners() as $event => $rawListeners) {
            foreach ($rawListeners as $rawListener) {
                if (is_string($rawListener)) {
                    $events[$event][] = [$rawListener, 'handle'];
                } elseif ($rawListener instanceof Closure) {
                    $events[$event][] = $this->stringifyClosure($rawListener);
                } elseif (is_array($rawListener) && count($rawListener) === 2) {
                    if (is_object($rawListener[0])) {
                        $rawListener[0] = get_class($rawListener[0]);
                    }

                    $events[$event][] = $rawListener;
                }
            }
        }

        return $events;
    }

    /**
     * Get a displayable string representation of a Closure listener.
     *
     * @param  \Closure  $rawListener
     * @return string
     */
    protected function stringifyClosure(Closure $rawListener)
    {
        $reflection = new ReflectionFunction($rawListener);

        $path = str_replace([base_path().DIRECTORY_SEPARATOR, '\\'], ['', '/'], $reflection->getFileName() ?: '');

        return $path.':'.$reflection->getStartLine();
    }

    /**
     * Filter the given events using the provided event name filter.
     *
     * @param  array  $events
     * @return array
     */
    protected function filterEvents(array $events)
    {
        if (! $eventName = $this->option('event')) {
            return $events;
        }

        return collect($events)->filter(
            fn ($listeners, $event) => str_contains($event, $eventName)
        )->toArray();
    }

    /**
     * Determine whether the user is filtering by an event name.
     *
     * @return bool
     */
    protected function filteringByEvent()
    {
        return ! empty($this->option('event'));
    }

    /**
     * Gets the raw version of event listeners from dispatcher object.
     *
     * @return array
     */
    protected function getRawListeners()
    {
        return $this->getLaravel()->make('events')->getRawListeners();
    }

    /**
     * Prints a line separator in console.
     *
     * @param  int  $width
     * @return void
     */
    protected function printSeparator(int $width)
    {
        $this->line(' '.str_repeat('_', $width - 1), 'fg=gray');
    }

    /**
     * Prints the list of listeners.
     *
     * @param  string[]  $listeners
     * @param  int  $width
     * @return void
     */
    protected function printsListeners($listeners, int $width)
    {
        $colorings = [
            '@' => '<fg=white>@</>',
            'Closure at: ' => '<fg=blue>Closure at: </>',
            ' - ' => '<fg=white> - </>',
            '(ShouldQueue)' => '<fg=blue>(ShouldQueue)</>',
            '(ShouldBroadcast)' => '<fg=blue>(ShouldBroadcast)</>',
        ];

        foreach ($listeners as $listener) {
            if (is_string($listener)) {
                $listener = 'Closure at: '.$listener;
            }

            if (is_array($listener)) {
                if ($this->implements($listener[0], ShouldQueue::class)) {
                    $listener[1] .= ' (ShouldQueue)';
                }

                if ($this->implements($listener[0], ShouldBroadcast::class)) {
                    $listener[1] .= ' (ShouldBroadcast)';
                }

                $listener = $listener[0].'@'.$listener[1];
            }

            $listener = '     - '.$listener;
            if (strlen($this->getPrintable($listener)) >= $width) {
                $listener = Str::limit($this->getPrintable($listener), $width - 3);
            }

            Str::of($listener)
                ->replace(array_keys($colorings), array_values($colorings))
                ->pipe(fn ($line) => $this->line($line, 'options=bold;fg=bright-blue'));
        }
    }

    /**
     * Prints the event in console.
     *
     * @param  string  $event
     * @return void
     */
    protected function printEvent(string $event)
    {
        $this->line('  '.$event, 'fg=yellow');
    }

    /**
     * Determines that the class implements the give interface.
     *
     * @param  string  $class
     * @param  string  $interface
     * @return bool
     */
    protected function implements(string $class, string $interface)
    {
        return in_array($interface, class_implements($class));
    }

    /**
     * Remove commandline styling tags from the string.
     *
     * @param $line
     * @return string
     */
    protected function getPrintable($line)
    {
        $line = str_replace('</>', '', $line);

        return preg_replace('/<fg=(.*?)>/', '', $line);
    }
}
