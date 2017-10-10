<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Symfony\Component\Process\ProcessUtils;

class CommandBuilder
{
    /**
     * Build the command for running the event in the foreground.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    public function buildForegroundCommand(Event $event)
    {
        $output = ProcessUtils::escapeArgument($event->output);

        return $this->ensureCorrectUser(
            $event, $event->command.($event->shouldAppendOutput ? ' >> ' : ' > ').$output.' 2>&1'
        );
    }

    /**
     * Build the command for running the event in the background.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    public function buildBackgroundCommand(Event $event)
    {
        $background = Application::formatCommandString('schedule:background').' "'.$event->mutexName().'"';

        $output = ProcessUtils::escapeArgument($event->getDefaultOutput());

        return $this->ensureCorrectUser(
            $event, '('.$background.' > '.$output.' 2>&1) > '.$output.' 2>&1 &'
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
