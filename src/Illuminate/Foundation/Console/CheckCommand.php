<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class CheckCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Checks the health of the current environment.";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->checkMcrypt();
    }

    private function checkMcrypt()
    {
        if (extension_loaded('mcrypt')) {
            $this->info('Dependency "MCrypt PHP Extension" found.');
        } else {
            $this->error('Dependency "MCrypt PHP Extension" not found.');
        }
    }
}