<?php

namespace Illuminate\Console;

use Illuminate\Contracts\Console\ConfirmHandler as ConfirmHandlerContract;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * This method only asks for confirmation in production.
     *
     * @param  string|null  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = null, $callback = null)
    {
        if ($this->laravel->bound(ConfirmHandlerContract::class)) {
            $handler = $this->laravel->make(ConfirmHandlerContract::class);
        } else {
            $handler = new ConfirmHandler();
        }
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
