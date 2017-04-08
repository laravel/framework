<?php

namespace Illuminate\Foundation\Console;

use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Foundation\Console\Presets\Preset;

class PresetCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'preset { type : The preset type (\'list\' to view available)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swap the front-end scaffolding for the application';

    protected $preset = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->preset = app(Preset::class);
        $this->preset->setCommand($this);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->argument('type') == 'list') {
            return $this->listPresets();
        }

        if (! in_array($this->argument('type'), $this->preset->availablePresets())) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        return $this->preset->{$this->argument('type')}();
    }


    /**
     * Output all registered presets
     *
     * @return void
     */
    private function listPresets()
    {
        collect($this->preset->availablePresets())
            ->each(function ($preset) {
                $this->info($preset);
            });
    }
}
