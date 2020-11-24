<?php

namespace Illuminate\Console;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * This method only asks for confirmation in production.
     *
     * @param  string  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = null, $callback = null)
    {
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;
        $warning = value($callback);

        if ($warning) {
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
            if ($this->getLaravel()->environment() === 'local') {
                $connection = $this->app['config']['database.default'];

                if ($this->app['config']['database.connections.'.$connection.'.host']) {
                    $host = $this->app['config']['database.connections.'.$connection.'.host'];
                }

                if ($this->app['config']['database.connections.'.$connection.'.write.host']) {
                    $host = $this->app['config']['database.connections.'.$connection.'.write.host'];
                }

                if ($host) {
                    $remoteDatabase = $host !== 'localhost' && $host !== '127.0.0.1';
                }

                if ($remoteDatabase) {
                    return 'You May Be Connected To A Remote Database!';
                }
            }

            if ($this->getLaravel()->environment() === 'production') {
                return 'Application In Production!';
            }

            return;
        };
    }
}
