<?php

namespace Illuminate\Foundation\Console\Presets;

use Illuminate\Support\Traits\Macroable;

class Preset
{
    use Macroable;

    /**
     * Preset Command.
     *
     * @var Illuminate\Console\Command
     */
    protected $command;

    /**
     * Register framework default presets.
     */
    public function __construct()
    {
        $this->registerReact();
        $this->registerBootstrap();
        $this->registerFresh();
    }

    /**
     * Set the command.
     *
     * @param Illuminate\Console\Command $command Preset Command
     * @return  void
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Get the available preset macros.
     *
     * @return array
     */
    public function availablePresets()
    {
        return array_keys(static::$macros);
    }

    /**
     * Register the react preset.
     *
     * @return void
     */
    private function registerReact()
    {
        static::macro('react', function () {
            React::install();
            $this->command->info('React scaffolding installed successfully.');
            $this->command->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
        });
    }

    /**
     * Register the bootstrap preset.
     *
     * @return void
     */
    private function registerBootstrap()
    {
        static::macro('bootstrap', function () {
            React::install();
            $this->command->info('Bootstrap scaffolding installed successfully.');
            $this->command->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
        });
    }

    /**
     * Register the fresh preset.
     *
     * @return void
     */
    private function registerFresh()
    {
        static::macro('fresh', function () {
            None::install();
            $this->command->info('Frontend scaffolding removed successfully.');
        });
    }
}
