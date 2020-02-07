<?php

namespace Illuminate\Foundation\Console;


use Illuminate\Console\Command;


class CommandsListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commands:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered commands';

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Signature', 'Class'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
        $commands = collect(Artisan::all())->map(function($command){
            return $this->getCommandInformation($command);
        })->toArray();

        $this->table($this->getHeaders(), $commands);
    }

    /**
     * Get the route information for a given command.
     *
     * @param  Symfony\Component\Console\Command\Command $command
     * @return array
     */
    protected function getCommandInformation(SymfonyCommand $command)
    {
        return [
            'signature' => $command->getName(),
            'namespace' => get_class($command)
        ];
    }

    /**
     * Get the table headers for the visible columns.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }
}