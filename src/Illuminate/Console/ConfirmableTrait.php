<?php

namespace Illuminate\Console;

trait ConfirmableTrait
{
    /**
     * The environment this command should be executed in.
     *
     * @var string
     */
    public $environmentToConfirm = 'Production';

    /**
     * Confirm before proceeding with the action.
     *
     * This method only asks for confirmation in production.
     *
     * @param  string  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning, $callback = null)
    {
        if ($warning === null){
            $warning = "Application In {$this->environmentToConfirm}!";
        }

        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

        $shouldConfirm = value($callback);

        if ($shouldConfirm) {
            if ($this->hasOption('force') && $this->option('force')) {
                return true;
            }

            $this->alert($warning);

            $confirmed = $this->confirm('Do you really wish to run this command?');

            if (! $confirmed) {
                $this->comment('Command Canceled!');

                return false;
            }
        }

        return true;
    }

    /**
     * Get the default confirmation callback.
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return $this->getLaravel()->environment() === strtolower($this->environmentToConfirm);
        };
    }
}
