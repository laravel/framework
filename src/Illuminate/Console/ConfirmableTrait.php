<?php

namespace Illuminate\Console;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * This method only asks for confirmation in production.
     *
     * @param  ?string  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = null, $callback = null)
    {
        $handler = $this->laravel->make(ConfirmHandler::class);
        $shouldConfirm = $callback !== null ? value($callback) : $handler::handle($this->laravel);

        if ($shouldConfirm) {
            if ($this->hasOption('force') && $this->option('force')) {
                return true;
            }

            $this->alert($warning ?? $handler::warning());

            $confirmed = $this->confirm('Do you really wish to run this command?');

            if (! $confirmed) {
                $this->comment('Command Canceled!');

                return false;
            }
        }

        return true;
    }
}
