<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;

class RepeatCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:repeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration & run it again';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->call('migrate:rollback');
        $this->call('migrate');
    }
}
