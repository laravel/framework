<?php

namespace Illuminate\Foundation\Console;

use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Traits\Macroable;

class PresetCommand extends Command
{
    use Macroable;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'preset { type? : The preset type. Omit to show all presets. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swap the front-end scaffolding for the application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('type'))) {
            return call_user_func(static::$macros[$this->argument('type')], $this);
        }

        $presets = array_merge(['none', 'bootstrap', 'vue', 'react'], array_keys(static::$macros));

        if (! in_array($this->argument('type'), $presets)) {
            throw new InvalidArgumentException('Invalid preset. Available presets are: '.implode($presets, ', '));
        }

        return $this->{$this->argument('type')}();
    }

    /**
     * Install the "fresh" preset.
     *
     * @return void
     */
    protected function none()
    {
        Presets\None::install();

        $this->info('Frontend scaffolding removed successfully.');
    }

    /**
     * Install the "bootstrap" preset.
     *
     * @return void
     */
    protected function bootstrap()
    {
        Presets\Bootstrap::install();

        $this->info('Bootstrap scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }

    /**
     * Install the "vue" preset.
     *
     * @return void
     */
    protected function vue()
    {
        Presets\Vue::install();

        $this->info('Vue scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }

    /**
     * Install the "react" preset.
     *
     * @return void
     */
    protected function react()
    {
        Presets\React::install();

        $this->info('React scaffolding installed successfully.');
        $this->comment('Please run "npm install && npm run dev" to compile your fresh scaffolding.');
    }
}
