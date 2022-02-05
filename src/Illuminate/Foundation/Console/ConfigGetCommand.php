<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ConfigGetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'config:get {key : The key of the config value to retrieve}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Returns the value of the given configuration key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info(json_encode(config($this->argument('key')), JSON_PRETTY_PRINT));
    }
}
