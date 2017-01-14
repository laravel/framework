<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Symfony\Component\Process\ProcessUtils;

class CommandBuilder
{
    /**
     * Build the command for the given event.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    public function buildCommand(Event $event)
    {
        if ($event->runInBackground) {
            return $this->buildBackgroundCommand($event);
        } else {
            return $this->buildForegroundCommand($event);
        }
    }

    /**
     * Build the command for running the event in the foreground.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function buildForegroundCommand(Event $event)
    {
        $output = ProcessUtils::escapeArgument($event->output);

        return $this->ensureCorrectUser(
            $event, $event->command.($event->shouldAppendOutput ? ' >> ' : ' > ').$output.' 2>&1'
        );
    }

    /**
     * Build the command for running the event in the foreground.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function buildBackgroundCommand(Event $event)
    {
        $output = ProcessUtils::escapeArgument($event->output);

        $redirect = $event->shouldAppendOutput ? ' >> ' : ' > ';

        $finished = Application::formatCommandString('schedule:finish').' "'.$event->mutexName().'"';

        return $this->ensureCorrectUser($event,
            '('.$event->command.$redirect.$output.' 2>&1 '.(windows_os() ? '&' : ';').' '.$finished.') > '
            .ProcessUtils::escapeArgument($event->getDefaultOutput()).' 2>&1 &'
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  string  $command
     * @return string
     */
    protected function ensureCorrectUser(Event $event, $command)
    {
        return $event->user && ! windows_os() ? 'sudo -u '.$event->user.' -- sh -c \''.$command.'\'' : $command;
    }
}
