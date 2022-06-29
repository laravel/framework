<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env')]
class EnvironmentCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'env';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current framework environment';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info(sprintf(
            'The application is on [%s] environment.',
            $this->laravel['env'],
        ));
    }
}
